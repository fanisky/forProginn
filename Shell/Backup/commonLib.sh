#this file is some function for backup.sh

function isBinary (){
    local res="$(/usr/bin/file -i $1)"

    if [[ "$res" =~ ^.*?(charset=binary).*?$ ]]
    then
        echo yes
    else
        echo no
    fi
}

function getFileType (){
    if [ ! -e $1 ]
    then
       echo 'no'
       return ;
    fi

    if [ -d $1 ]
    then
        echo 'dir'
    elif [ -f $1 ]
    then
        echo 'file'
    fi
}

function emptyFile (){
    if [[ ! -e $1 ]]
    then
        /bin/touch $1
    else
        /bin/cat /dev/null > $1
    fi
}

function trimSpace (){
    local res="$1"
    res=$(echo "$res"|/usr/bin/tr '\n' ' ')
    echo "$res"|/bin/sed -r 's/^[ ]+//'|/bin/sed -r 's/[ ]+$//'
}

function isBinLogOpen (){
    local L_mysql=$1
    local L_user="$2"
    local L_pass="$3"

    local res=$($L_mysql -u${L_user} -p${L_pass} -e "show global variables where variable_name='log_bin'" 2>/dev/null)

    if [ -z "$res" ]
    then
        echo "error"
	return ;
    fi
    
    res=$(trimSpace "$res")

    if [[ "$res" =~ ^.*ON$ ]]
    then
        echo "yes"
    else
        echo "no"
    fi
}

function getBackupDB (){
    local L_mysql=$1
    local L_user="$2"
    local L_pass="$3"
    local L_dont_backup_dbs=($4)

    local res=$($L_mysql -u$L_user -p$L_pass -e 'show databases;' 2>/dev/null)

    if [ -z "$res" ]
    then
        echo 'error';return ;
    fi

    res=$(trimSpace "$res")
    res=${res/"Database"/""}
    res="$res "

    for item in ${L_dont_backup_dbs[*]};
    do
        res=${res/" $item "/" "} #may be bug
    done

    res=$(trimSpace "$res")
    echo "$res"
}

function isDumpError (){
    local ret_code=0
    local mysqldump_errfile=$1

    if [ -s $mysqldump_errfile ]
    then
        ret_code=2
    else
        ret_code=1
    fi
	
    /bin/rm -f $mysqldump_errfile

    echo $ret_code
}

function dumpErrorHandler (){
    local L_mailx=$1
    local L_backup_filename=$2
    local L_is_delete=$3

    if [[ $L_is_delete = "yes" && -e $L_backup_filename ]]
    then
        /bin/rm -f $L_backup_filename
    fi

    #if exists mail func,send mail to someone for tell the warning
    mail_subject="mysqldump error"
    mail_content="mysqldump error occur, help please"
	
    #format like a@163.com,b@163.com,c@163.com...
    rec="415445@qq.com,2596433516@qq.com"
    
    #make sure that you had config your mail smtp server and username,password...
    if [ $(isBinary $L_mailx) = 'yes' ]
    then
        echo "$mail_content" | $L_mailx -s "$mail_subject" $rec
    fi
}

function fileKeep (){
    local L_backup_dir=$1
    local L_cur_num=$(/bin/ls $L_backup_dir|/usr/bin/wc -l)
    local L_all_files=($(/bin/ls -tr $backup_dir))

    if [ $L_cur_num -eq 13 ]
    then
        /bin/rm -rf $L_backup_dir/${L_all_files[0]}
    fi
}








































