<?php

namespace RZP\Models\Merchant\Analytics;

use RZP\Models\Payment;
use RZP\Models\Merchant\Service;

final class DataProcessor
{
    private $merchantService;

    public function __construct()
    {
        $this->merchantService = new Service();
    }

    public function processMerchantAnalyticsResponse(array $response): array
    {
        foreach ($response as $aggregationName => &$aggregation)
        {
            if (in_array($aggregationName, Constants::CR_RELATED_AGGREGATION_NAMES))
            {
                $this->calculateCRAndModifyResult($aggregation);
            }

            if (in_array($aggregationName, Constants::SR_RELATED_AGGREGATION_NAMES))
            {
                $this->calculateSRAndModifyResult($aggregation);
            }

            if (in_array($aggregationName, Constants::ERROR_METRICS_RELATED_AGGREGATION_NAMES))
            {
                $this->calculateErrorMetricsAndModifyResult($aggregation, $aggregationName);
            }
        }

        return $response;
    }

    private function calculateCRAndModifyResult(array &$aggregation): void
    {
        /**
         * $groupedCounts is associate array, which is used to group the data
         * acc. to group key and holds the counts of each group.
         * Key of associate array => Group key.
         * Value of associate array => Counts of each group.
         * For more details Refer => Readme file of DataProcessor
         */
        $groupedCounts = [];
        /**
         * $otherFields is associate array, which is used to group the data
         * acc. to group key and holds other fields apart from grouping fields for each group.
         * Key of associate array => Group key.
         * Value of associate array => Other fields apart from grouping fields for each group.
         * For more details Refer => Readme file of DataProcessor
         */
        $otherFields = [];
        $data = $aggregation[Constants::RESULT];
        $groupingFields = Constants::GROUP_BY_FIELDS_FOR_CR;

        foreach ($data as $result)
        {
            $groupKey = $this->extractGroupKey($result, $groupingFields);
            $this->updateGroupedCounts($groupedCounts, $groupKey, $result);
            $this->updateOtherFields($otherFields, $groupKey, $result, $groupingFields);
        }

        $groupedCounts = $this->calculatePercentage(
            $groupedCounts,
            Constants::TOTAL_NUMBER_OF_SUBMIT_EVENTS,
            Constants::TOTAL_NUMBER_OF_OPEN_EVENTS,
        );

        $aggregation[Constants::RESULT] = $this->formatResult($otherFields, $groupedCounts);
        $aggregation[Constants::TOTAL] = count($aggregation[Constants::RESULT]);
    }

    private function calculateSRAndModifyResult(array &$aggregation): void
    {
        /**
         * $groupedCounts is associate array, which is used to group the data
         * acc. to group key and holds the counts of each group.
         * Key of associate array => Group key.
         * Value of associate array => Counts of each group.
         * For more details Refer => Readme file of DataProcessor.
         */
        $groupedCounts = [];
        /**
         * $otherFields is associate array, which is used to group the data
         * acc. to group key and holds other fields apart from grouping fields for each group.
         * Key of associate array => Group key.
         * Value of associate array => Other fields apart from grouping fields for each group.
         * For more details Refer => Readme file of DataProcessor.
         */
        $otherFields = [];
        $data = $aggregation[Constants::RESULT];
        $groupingFields = Constants::GROUP_BY_FIELDS_FOR_SR;

        foreach ($data as $result)
        {
            $groupKey = $this->extractGroupKey($result, $groupingFields);
            $this->updateSuccessfulPaymentsCounts($groupedCounts, $groupKey, $result);
            $this->updateTotalPaymentsCounts($groupedCounts, $groupKey, $result);
            $this->updateOtherFields($otherFields, $groupKey, $result, $groupingFields);
        }

        $groupedCounts = $this->calculatePercentage(
            $groupedCounts,
            Constants::NUMBER_OF_SUCCESSFUL_PAYMENTS,
            Constants::NUMBER_OF_TOTAL_PAYMENTS
        );

        $aggregation[Constants::RESULT] = $this->formatResult($otherFields, $groupedCounts);
        $aggregation[Constants::TOTAL] = count($aggregation[Constants::RESULT]);
    }

    private function calculateErrorMetricsAndModifyResult(array &$aggregation, string $aggregationName): void
    {
        $data = $aggregation[Constants::RESULT];

        foreach ($data as &$result)
        {
            $result[Constants::LAST_SELECTED_METHOD] = Constants::METHOD_MAPPING[$result[Constants::LAST_SELECTED_METHOD]] ?? $result[Constants::LAST_SELECTED_METHOD];

            $result[Constants::ERROR_SOURCE] = $this->merchantService->getErrorSourceCategoryForFailureAnalysis(
                $result[Constants::INTERNAL_ERROR_CODE],
                $result[Constants::LAST_SELECTED_METHOD]
            );

            $result[Constants::ERROR_DESCRIPTION] = $this->getErrorReasonForErrorMetrics(
                $result[Constants::INTERNAL_ERROR_CODE],
                $result[Constants::LAST_SELECTED_METHOD]
            );

            // INTERNAL_ERROR_CODE and LAST_SELECTED_METHOD is used for getting error source and description.
            // Unsetting values as these are no longer required.
            // Note: Only for method level data we need LAST_SELECTED_METHOD for grouping.
            unset($result[Constants::INTERNAL_ERROR_CODE]);

            if ($aggregationName !== Constants::CHECKOUT_METHOD_LEVEL_TOP_ERROR_REASONS)
            {
                unset($result[Constants::LAST_SELECTED_METHOD]);
            }
        }

        unset($result);

        /**
         * $groupedCounts is associate array, which is used to group the data
         * acc. to group key and holds the counts of error resons fpr each group.
         * Key of associate array => Group key.
         * Value of associate array => Associate array holding counts of error reasons for each group.
         * For more details Refer => Readme file of DataProcessor
         */
        $groupedCounts = [];
        /**
         * $otherFields is associate array, which is used to group the data
         * acc. to group key and holds other fields apart from grouping fields for each group.
         * Key of associate array => Group key.
         * Value of associate array => Other fields apart from grouping fields for each group.
         * For more details Refer => Readme file of DataProcessor
         */
        $otherFields = [];
        $groupingFields = Constants::GROUP_BY_FIELDS_FOR_ERROR_METRICS;

        foreach ($data as $result)
        {
            $groupKey = $this->extractGroupKey($result, $groupingFields);
            $this->updateGroupedCountsForErrorMetrics($groupedCounts, $groupKey, $result);
            $this->updateOtherFields($otherFields, $groupKey, $result, $groupingFields);
        }

        $this->filterTopErrorReasonsForEachGroup($groupedCounts);

        $aggregation[Constants::RESULT] = $this->formatResultForErrorMetrics($otherFields, $groupedCounts);
        $aggregation[Constants::TOTAL] = count($aggregation[Constants::RESULT]);
    }

    private function calculatePercentage(array $groupedCounts, string $dividend, string $divisor): array
    {
        foreach ($groupedCounts as &$data)
        {
            if ($data[$divisor] === 0)
            {
                $data[Constants::VALUE] = 0;
            }
            else
            {
                $data[Constants::VALUE] = ($data[$dividend] * 100) / $data[$divisor];
            }
        }

        return $groupedCounts;
    }

    private function extractGroupKey($result, $groupingFields): string
    {
        $groupKey = [];

        foreach ($result as $key => $value)
        {
            if ((in_array($key, $groupingFields) === false) and $key !== Constants::VALUE)
            {
                $groupKey[] = $value;
            }
        }

        sort($groupKey);

        return implode('-', $groupKey);
    }

    private function updateGroupedCounts(&$groupedCounts, $groupKey, $result): void
    {
        if (isset($groupedCounts[$groupKey]) === false)
        {
            $groupedCounts[$groupKey] = [
                Constants::TOTAL_NUMBER_OF_SUBMIT_EVENTS => 0,
                Constants::TOTAL_NUMBER_OF_OPEN_EVENTS   => 0,
            ];
        }

        if ($result[Constants::SUBMIT_EVENT])
        {
            $groupedCounts[$groupKey][Constants::TOTAL_NUMBER_OF_SUBMIT_EVENTS] += $result[Constants::VALUE];
        }

        if ($result[Constants::OPEN_EVENT])
        {
            $groupedCounts[$groupKey][Constants::TOTAL_NUMBER_OF_OPEN_EVENTS] += $result[Constants::VALUE];
        }
    }

    private function updateGroupedCountsForErrorMetrics(&$groupedCounts, $groupKey, $result): void
    {
        if (isset($groupedCounts[$groupKey][$result[Constants::ERROR_DESCRIPTION]]) === false)
        {
            $groupedCounts[$groupKey][$result[Constants::ERROR_DESCRIPTION]] = 0;
        }

        $groupedCounts[$groupKey][$result[Constants::ERROR_DESCRIPTION]] += $result[Constants::VALUE];
    }

    private function updateSuccessfulPaymentsCounts(&$groupedCounts, $groupKey, $result): void
    {
        if (isset($groupedCounts[$groupKey]) === false)
        {
            $groupedCounts[$groupKey] = [];
            $groupedCounts[$groupKey][Constants::NUMBER_OF_TOTAL_PAYMENTS] = 0;
            $groupedCounts[$groupKey][Constants::NUMBER_OF_SUCCESSFUL_PAYMENTS] = 0;
        }

        if ($this->isPaymentSuccessfulStatus($result[Payment\Entity::STATUS]))
        {
            $groupedCounts[$groupKey][Constants::NUMBER_OF_SUCCESSFUL_PAYMENTS] += $result[Constants::VALUE];
        }
    }

    private function isPaymentSuccessfulStatus(string $status): bool
    {
        return in_array($status, [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED,
        ]);
    }

    private function updateTotalPaymentsCounts(&$groupedCounts, $groupKey, $result): void
    {
        if (isset($groupedCounts[$groupKey]) === false)
        {
            $groupedCounts[$groupKey] = [];
            $groupedCounts[$groupKey][Constants::NUMBER_OF_TOTAL_PAYMENTS] = 0;
            $groupedCounts[$groupKey][Constants::NUMBER_OF_SUCCESSFUL_PAYMENTS] = 0;
        }

        $groupedCounts[$groupKey][Constants::NUMBER_OF_TOTAL_PAYMENTS] += $result[Constants::VALUE];
    }

    private function updateOtherFields(&$otherFields, $groupKey, $result, $groupingFields): void
    {
        if (isset($otherFields[$groupKey]) === false)
        {
            foreach ($result as $key => $value)
            {
                if (in_array($key, $groupingFields) === false)
                {
                    $otherFields[$groupKey][$key] = $value;
                }
            }
        }
    }

    private function formatResult($otherFields, $groupedCounts): array
    {
        foreach ($groupedCounts as $groupKey => $groupedCount)
        {
            $otherFields[$groupKey][Constants::VALUE] = $groupedCount[Constants::VALUE];
        }

        return array_values($otherFields);
    }

    private function formatResultForErrorMetrics($otherFields, $groupedCounts): array
    {
        foreach ($groupedCounts as $groupKey => $errorReasons)
        {
            $otherFields[$groupKey][Constants::ERROR_REASONS] = $errorReasons;
        }

        return array_values($otherFields);
    }

    private function filterTopErrorReasonsForEachGroup(array &$groupedCounts): void
    {
        foreach ($groupedCounts as $groupKey => $errorReasons)
        {
            $groupedCounts[$groupKey] = $this->getTopErrorReasons($errorReasons);
        }
    }

    private function getTopErrorReasons(array $data): array
    {
        // Sort the array based on values in descending order
        uasort($data, fn($a, $b) => $b <=> $a);

        return array_slice($data, 0, Constants::ERROR_METRICS_LIMIT);
    }

    public function getErrorReasonForErrorMetrics($errorCode, $method)
    {
        $errorReason = $this->merchantService->getErrorReason($errorCode, $method);

        return Constants::CUSTOM_ERROR_DESCRIPTION_MAPPING[$errorReason] ?? $errorReason;
    }
}
