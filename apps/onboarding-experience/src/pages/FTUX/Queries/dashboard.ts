import { gql } from 'graphql-tag';

export const MERCHANT_ONBOARDING_DATA_QUERY = gql`
  query MerchantOnboardingData(
    $requestedData: [MerchantOnboardingDataRequestEnum!]
    $featureFlagNames: [String!]
    $workflow: MerchantSelfServeWorkflowEnum
  ) {
    merchantOnboardingData(
      requestedData: $requestedData
      featureFlagNames: $featureFlagNames
      workflow: $workflow
    ) {
      featureFlags
      selectedPlugins {
        website
        selectedPlugin
      }
      supportedPlugins {
        name
        icon
        integrationGuide
        integrationUrl
      }
      selfServeWorkflowStatus {
        ... on MerchantSelfServeWorkflowStatusSuccessResponse {
          code
          success
          message
          selfServeWorkflow {
            isWorkflowExits
            workflowStatus
            permission
            isRequestUnderBvsValidation
            rejectionReason
            needsClarificationMessage
            customerActions
            bankAccountId
            createdAt
            rejectedAt
          }
        }
        ... on MerchantSelfServeWorkflowStatusFailureResponse {
          code
          success
          message
        }
      }
      websiteVerificationUpdateStatus {
        ... on MerchantWebsiteVerificationSuccessResponse {
          code
          success
          message
          verificationStatus {
            currentStatus
            currentStatusUpdatedAt
            mainPageUrl
            websiteVerificationStage {
              workflowExist
              mccCheckStatus
              negativeKeywordCheckStatus
              bvsCheckStatus
            }
            websiteVerificationPageStatus {
              terms {
                url
                verified
              }
              privacy {
                url
                verified
              }
              refund {
                url
                verified
              }
              shipping {
                url
                verified
              }
              contact {
                url
                verified
              }
            }
          }
        }
        ... on MerchantWebsiteVerificationFailureResponse {
          code
          success
          message
        }
      }
    }
  }
`;
