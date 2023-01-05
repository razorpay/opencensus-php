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


echo "#### Sonar Analysis Report" >> comment.txt
echo " " >> comment.txt
echo "| Metric | Master | Current Branch |" >> comment.txt
echo "| --- | ----------- | ------------------- | " >> comment.txt
echo "| Code Coverage | ${code_coverage_exact_prod} | ${code_coverage_exact_dev} |" >> comment.txt
echo "| Code Smell | ${code_smell_prod} | ${code_smell_dev} |" >> comment.txt
echo "| Code Duplication Lines | ${code_duplication_prod_lines} | ${code_duplication_dev_lines} |" >> comment.txt
echo "| Code Duplication | ${code_duplication_prod_percentage}% | ${code_duplication_dev_percentage}% |" >> comment.txt


echo Code coverage dev exact: $code_coverage_exact_dev
echo Code coverage dev: $code_coverage_dev
echo Code coverage prod exact: $code_coverage_exact_prod
echo Code coverage prod: $code_coverage_prod
echo code_duplication_dev_percentage: $code_duplication_dev_percentage
echo code_duplication_prod_percentage: $code_duplication_prod_percentage

echo code_duplication_dev_lines: $code_duplication_dev_lines
echo code_duplication_prod_lines: $code_duplication_prod_lines

if [ -z "$code_coverage_dev" ] || [ $code_coverage_dev = null ]; then
    echo "**Status**: 🚫 Sonar Checks Failed, code coverage for this PR is not found"  >> comment.txt
    exit 1
fi

if [ $code_coverage_exact_dev -lt $code_coverage_exact_prod ]; then
    echo "**Status**: 🚫 Sonar Checks Failed, current code coverage of production ($code_coverage_exact_prod) is getting reduced. Try pulling the latest master in your branch or try covering the new code in your test cases." >> comment.txt
    exit 1
else
    echo "**Status**: ✅ Sonar Checks Passed, code coverage threshold met" >> comment.txt
    exit 0
fi
