#!/bin/sh
get_spinnaker_config_value() {
  defaultInstanceValue="bvt-1"
  count=0
  statusCode=$(curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' \
    --header "Authorization: Bearer ${GIT_TOKEN}")
  cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
  SPINNAKER_HEADER="Cookie: SESSION=$cookies"
  bvt1_pipeline_id="1206c395-2c51-4567-aa8a-ff5c3f8b9a72"
  bvt2_pipeline_id="751ba446-f187-4772-bb27-28cedfa0b864"
  # For now we using 2 parallel BVT pipelines. hence pulling the child pipeline queue length. In future once we have more BVT pipelines, we can have below approach
  # Pull the Running PR count from parent pipeline and get the instance count - bvt1, bvt2 and so on. Store it in array and allocate instance accordingly
  if [ "$statusCode" = 200 ]; then
     bvt1_pipeline_count=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${bvt1_pipeline_id}&statuses=NOT_STARTED&limit=50&statuses=RUNNING" \
      -H "${SPINNAKER_HEADER}" | jq length)
     bvt2_pipeline_count=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${bvt2_pipeline_id}&statuses=NOT_STARTED&limit=50&statuses=RUNNING" \
      -H "${SPINNAKER_HEADER}" | jq length)
  fi
  # Condition to check the current instance values tag
  if [ "$bvt1_pipeline_count" -gt "$bvt2_pipeline_count" ]; then
    defaultInstanceValue="bvt-2"
  fi
}
get_spinnaker_config_value
echo $defaultInstanceValue