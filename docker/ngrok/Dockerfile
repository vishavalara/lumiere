FROM alpine

RUN apk add --no-cache bash unzip

# download ngrok
WORKDIR /tmp/ngrok

RUN wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-386.zip
RUN unzip ngrok-*.zip
RUN rm ngrok-*.zip
RUN mv ngrok /usr/local/bin/

# add default configuration file
RUN mkdir -p /root/.ngrok2

ADD ngrok.yml /root/.ngrok2/

# prepare entry point
WORKDIR /usr/local/bin

ADD docker-entrypoint.sh /usr/local/bin/

RUN ["chmod", "+x", "/usr/local/bin/docker-entrypoint.sh"]

ENTRYPOINT ["docker-entrypoint.sh"]

CMD [ "start" ]

EXPOSE 8040
