#!/bin/sh
run_label_check()
{
if [ "${USER_CHECK}" = "fail" ]
then
  if [ "${LABEL_FLAG}" = "true" ]
  then
    echo "Even though labelled github user is not payments bu user"
    exit 0
  else
    echo "Github user is not payments bu user"
    exit 0
  fi
  elif [ "${USER_CHECK}" = "success" ]; then
    if [ "${LABEL_FLAG}" = "true" ]
    then
      echo "PR is labeled with $LABEL"
      exit 0
    else
      echo "PR is not labeled with $LABEL"
      exit 1
    fi
  else
    echo "Unable to fetch Github User..Please re-run the job again"
    exit 1
fi
}
run_label_check
