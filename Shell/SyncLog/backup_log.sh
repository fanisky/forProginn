#!/bin/sh
#对日志进行打包，减少硬盘的占用
tar -zcvf /host/logs/log.`date +%y%m%d%H%M%S`.tar.gz  /host/logs/*.log.com --remove-files
