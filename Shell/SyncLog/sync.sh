#!/bin/bash

#后面会删除已复制的log，为了避免删除新生成的log，此处先将必要的log移到临时工作目录
ssh jxdx@host "mv /data/node/logs/* /data/logs/"
ssh jxdx@host "mv /data/web/logs/* /data/logs/"
#复制日志到本地
scp jxdx@host:/data/logs/* /host/logs/
#删除已经复制到本地的远程日志文件
ssh jxdx@host "rm -rf /data/logs/*"
 
