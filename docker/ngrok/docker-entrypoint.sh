#!/bin/bash

if [[ "$1" == start ]]; then

	if [[ '' != "$NGROK_TOKEN" ]]; then
		ngrok authtoken $NGROK_TOKEN
	fi

	if [[ '' != "$NGROK_SUBDOMAIN" ]]; then
		exec ngrok http --log=stdout --subdomain=$NGROK_SUBDOMAIN --host-header=rewrite https://wp.test
	fi

else

	# allow one-off command execution
	exec "$@"

fi
