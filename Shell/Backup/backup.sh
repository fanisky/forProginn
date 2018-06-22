#!/bin/bash

#command variable
g_mkdir=/bin/mkdir
g_mysql=/usr/bin/mysql
g_mysqldump=/usr/bin/mysqldump
g_mysqladmin=/usr/bin/mysqladmin
g_date=/bin/date
g_mv=/bin/mv
g_mailx=/bin/mailx
g_rsync=/usr/bin/rsync
g_sed=/bin/sed
g_tar=/bin/tar
g_cp=/bin/cp
g_rm=/bin/rm
g_gzip=/bin/gzip
g_ls=/bin/ls
g_wc=/usr/bin/wc

#mysql variable
user=root
password=
backup_dbs=""
dont_backup_dbs=(information_schema mysql performance_schema test)

#backup dir variable(this directory is used to store backup file,so rsync server's module must point this directory, no "/" at last)
backup_dir=/data/backupv2/files

mysqldump_errfile=/tmp/mysqldump_err

#import func lib
source $(cd $(dirname $0); pwd)/commonLib.sh

######################################################

####################command inspect###################
if [ $(isBinary $g_mysql) = 'no' ]
then
    echo "mysql command must be binary";exit 1;
fi

if [ $(isBinary $g_mysqldump) = 'no' ]
then
    echo "mysqldump command must be binary";exit 1;
fi

if [ $(isBinary $g_mysqladmin) = 'no' ]
then
    echo "mysqladmin command must be binary";exit 1;
fi

if [ $(isBinary $g_rsync) = 'no' ]
then
    echo "rsync command must be binary";exit 1;
fi

##################create sub directory################
if [ ! -d $backup_dir ]
then
    $g_mkdir -p $backup_dir
fi

sub_dir=$($g_date "+%Y_%m_%d_%H")

if [ ! -d $backup_dir/$sub_dir ]
then
    $g_mkdir $backup_dir/$sub_dir
    $g_mkdir $backup_dir/$sub_dir/mysql
    $g_mkdir $backup_dir/$sub_dir/file
fi

mysql_dir=$backup_dir/$sub_dir/mysql
file_dir=$backup_dir/$sub_dir/file

###############backup file and directory##############
#directory and file must be absolute path
needed_backup_file_or_dir=(/data/www/html)

if [ ${#needed_backup_file_or_dir[@]} -ne 0 ]
then
    for item in ${needed_backup_file_or_dir[@]}
    do
        case $(getFileType $item) in
            dir)
	        item=$(echo $item|$g_sed -r 's/^(.*)\/$/\1/')
                $g_rsync -auzS $item $file_dir

		cd $file_dir
		
		tmp_item=${item##*/}
                $g_tar -zcf ./${tmp_item}.tar.gz ./${tmp_item}
                $g_rm -rf $file_dir/${tmp_item}

		cd - >/dev/null
	    ;;
	    file)
                $g_cp $item $file_dir
		
                tmp_item=${item##*/}
                $g_gzip -f $file_dir/$tmp_item
	    ;;
	    no)
                #do something or continue?
	    ;;
        esac
    done
fi

###################backup mysql data##################
#check whether open the bin-log
if [ $(isBinLogOpen $g_mysql "$user" "$password") != 'yes' ]
then
    echo "mysql error or not open mysql bin-log";exit 1;
fi

#empty file which record the error info of mysqldump
emptyFile $mysqldump_errfile

#get all needed backup db
backup_dbs=$(getBackupDB $g_mysql "$user" "$password" "${dont_backup_dbs[*]}")

if [ "$backup_dbs" = 'error' ]
then
    echo "mysql error";exit 1;    
fi

#start mysqldump command
mysql_backup_name=mysql_backup_data.sql.gz
backup_filename=$mysql_dir/$mysql_backup_name

$g_mysqldump -u${user} -p${password} --flush-logs --lock-all-tables --master-data=2 --add-drop-database --log-error=${mysqldump_errfile} --databases ${backup_dbs} 2>/dev/null | gzip -7>$backup_filename

#check whether mysqldump error occur
if [ $(isDumpError $mysqldump_errfile) -eq 2 ] #error occur
then
    dumpErrorHandler $g_mailx $backup_filename yes
    exit 1;
fi

#############keep backup directory number#############
fileKeep $backup_dir

exit 0;


