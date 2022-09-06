#!/bin/sh
run_alternate_label_check()
{
if [ "${USER_CHECK}" = "success" ]
then
  if [ "${LABEL_ONE_FLAG}" = "true" ]
  then
    if [ "${LABEL_TWO_FLAG}" = "true" ] || [ "${LABEL_THREE_FLAG}" = "true" ]
    then
      echo "Incorrectly labelled. Only one of Migration/Non-Migration/Archival label should be present."
      exit 1
    else
      echo "PR is labelled with $LABEL_ONE"
      exit 0
    fi
  else
    if [ "${LABEL_TWO_FLAG}" = "true" ] && [ "${LABEL_THREE_FLAG}" = "true" ]
    then
      echo "Incorrectly labelled. Both $LABEL_TWO and $LABEL_THREE labels are present."
      exit 1
    elif [ "${LABEL_TWO_FLAG}" = "true" ] || [ "${LABEL_THREE_FLAG}" = "true" ]
    then
      echo "PR is labelled correctly. One of Migration/Non-Migration/Archival label is present."
      exit 0
    else
      echo "Incorrectly labelled. One of Migration/Non-Migration/Archival label should be present."
      exit 1
    fi
  fi
elif [ "${USER_CHECK}" = "fail" ]
then
  echo "Github user is not a member of Payments BU"
  exit 0
else
  echo "Unable to fetch Github user. Please re-run the job again"
  exit 1
fi
}
run_alternate_label_check
