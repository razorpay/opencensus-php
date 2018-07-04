#!/bin/sh
# See https://github.com/ChALkeR/notes/blob/master/Stealing-Travis-secure-variables.md
# https://github.com/razorpay/security/issues/21 for reference on why we need this

if [ $# -eq 0 ]; then
    echo "Please do not print out environment variables in CI. Talk to @razorpay/security."
    exit 0
else
	/usr/bin/oldenv "$@"
fi
