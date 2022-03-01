**部署时需修改env中的两个SITE URL，对应实际部署的两个赛题的URL**
启动使用如下命令
还需要手动添加reCapture的site
docker build -t bot .
docker run -itd -p port:3000 --cap-add SYS_ADMIN --env-file env bot
