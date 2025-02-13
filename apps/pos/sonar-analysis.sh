CURL_URL_DEV=$SONAR_HOST'/api/measures/component_tree?metricKeys=coverage,code_smells,duplicated_lines_density,duplicated_lines&component='$PROJECT_KEY_DEV
CURL_URL_PROD=$SONAR_HOST'/api/measures/component_tree?metricKeys=coverage,code_smells,duplicated_lines_density,duplicated_lines&component='$PROJECT_KEY_PROD
# apt-get update
apt-get install jq -y
sleep 5

curl --location --request GET $CURL_URL_DEV -u $SONAR_TOKEN:"" > sonar_dev.json
curl --location --request GET $CURL_URL_PROD -u $SONAR_TOKEN:"" > sonar_prod.json

code_coverage_threshold=81

code_coverage_exact_dev=$(jq -r '.baseComponent.measures[] | select(.metric | contains("coverage")).value' sonar_dev.json)
code_coverage_dev=$(jq -r '.baseComponent.measures[0].value' sonar_dev.json | cut -d "." -f 1)

code_coverage_exact_prod=$(jq -r '.baseComponent.measures[] | select(.metric | contains("coverage")).value' sonar_prod.json)
code_coverage_prod=$(jq -r '.baseComponent.measures[0].value' sonar_prod.json | cut -d "." -f 1)

code_smell_dev=$(jq -r '.baseComponent.measures[] | select(.metric | contains("code_smells")).value' sonar_dev.json)
code_smell_prod=$(jq -r '.baseComponent.measures[] | select(.metric | contains("code_smells")).value' sonar_prod.json)

code_duplication_dev_percentage=$(jq -r '.baseComponent.measures[] | select(.metric == "duplicated_lines_density").value' sonar_dev.json)
code_duplication_prod_percentage=$(jq -r '.baseComponent.measures[] | select(.metric == "duplicated_lines_density").value' sonar_prod.json)

code_duplication_dev_lines=$(jq -r '.baseComponent.measures[] | select(.metric == "duplicated_lines").value' sonar_dev.json)
code_duplication_prod_lines=$(jq -r '.baseComponent.measures[] | select(.metric == "duplicated_lines").value' sonar_prod.json)


echo "#### $FILE_HEADING" >> $FILE_NAME
echo " " >> $FILE_NAME
echo "| Metric | Master | Current Branch |" >> $FILE_NAME
echo "| --- | ----------- | ------------------- | " >> $FILE_NAME
echo "| Code Coverage | ${code_coverage_exact_prod} | ${code_coverage_exact_dev} |" >> $FILE_NAME
echo "| Code Smell | ${code_smell_prod} | ${code_smell_dev} |" >> $FILE_NAME
echo "| Code Duplication Lines | ${code_duplication_prod_lines} | ${code_duplication_dev_lines} |" >> $FILE_NAME
echo "| Code Duplication | ${code_duplication_prod_percentage}% | ${code_duplication_dev_percentage}% |" >> $FILE_NAME


echo Code coverage dev exact: $code_coverage_exact_dev
echo Code coverage dev: $code_coverage_dev
echo Code coverage prod exact: $code_coverage_exact_prod
echo Code coverage prod: $code_coverage_prod
echo code_duplication_dev_percentage: $code_duplication_dev_percentage
echo code_duplication_prod_percentage: $code_duplication_prod_percentage

echo code_duplication_dev_lines: $code_duplication_dev_lines
echo code_duplication_prod_lines: $code_duplication_prod_lines


