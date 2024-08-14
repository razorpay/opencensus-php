import { gql } from 'graphql-tag';

export const SALES_ONBOARDED_MERCHANTS = gql`
  query SalesOnboardedMerchants(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $startDate: PositiveInt!
    $endDate: PositiveInt!
    $status: String!
  ) {
    salesOnboardedMerchants(
      limit: $limit
      offset: $offset
      startDate: $startDate
      endDate: $endDate
      status: $status
    ) {
      ... on SalesOnboardedMerchants {
        __typename
        limit
        offset
        total
        hasMore
        totalMerchantsOnboarded
        statusCounts {
          activated
          rejected
          needsClarification
          kycQualifiedStb
          pending
          underReview
        }
        merchants {
          createdAt
          merchantId
          merchantName
          merchantMobile
          progressCompletion
          status
        }
      }
      ... on SalesOnboardedMerchantsError {
        __typename
        code
        success
        message
      }
    }
  }
`;

export const MODULAR_CONFIG = gql`
  query MerchantModularOnboardingDetailsAsSales($merchantId: String!) {
    merchantModularOnboardingDetailsAsSales(merchantId: $merchantId) {
      ... on merchantModularOnboardingDetailsSuccessResponse {
        __typename
        success
        workflowData {
          id
          progress
          status
          milestones {
            canSubmit
            name
            status
            steps {
              ... on ModularOnboardingStepInterface {
                __typename
                name
                progress
                status
              }
              ... on ModularOnboardingStepWithModularComponents {
                __typename
                name
                progress
                status
                meta {
                  title
                  description
                }
                modularComponents {
                  fields {
                    ... on ModularOnboardingFieldWithStringValue {
                      ...ModularOnboardingFieldFragment
                      stringValue: value
                    }
                    ... on ModularOnboardingFieldWithStringArrayValue {
                      ...ModularOnboardingFieldFragment
                      stringArrayValue: value
                    }
                    ... on ModularOnboardingFieldForDocumentUpload {
                      ...ModularOnboardingFieldFragment
                      documentUploadValue: value {
                        fileId
                        fileStoreId
                        name
                        size
                      }
                    }

                    ... on ModularOnboardingFieldForArrayOfDocumentsUpload {
                      ...ModularOnboardingFieldFragment
                      arrayOfDocumentsUploadValue: value {
                        name
                        size
                        fileStoreId
                      }
                    }
                    ... on ModularOnboardingFieldForDeviceCharges {
                      ...ModularOnboardingFieldFragment
                      orderSummary: value {
                        advanceRentalCharge
                        deviceCharge
                        gst
                        orderId
                        paperRollCharge
                        shippingCharge
                        totalOrderCharge
                        totalRentalCharge
                        rentalCharge {
                          deviceName
                          fee
                          gst
                          renewal
                        }
                      }
                    }
                    ... on ModularOnboardingFieldForOrderSummaryItem {
                      ...ModularOnboardingFieldFragment
                      addedDevices: value {
                        deviceName
                        itemId
                        paperRollCharge
                        paperRollQuantity
                        quantity
                        renewal
                        rentalCharge
                        setupCharge
                        totalAdvanceRentalCharge
                        totalPaperRollCharge
                        totalRentalCharge
                        totalSetupCharge
                        rentalChargeType
                        setupChargeType
                      }
                    }

                    ... on ModularOnboardingFieldWithBooleanValue {
                      ...ModularOnboardingFieldFragment
                      booleanValue: value
                    }

                    ... on ModularOnboardingFieldForJsonValues {
                      ...ModularOnboardingFieldFragment
                      jsonValue: value
                    }
                  }
                  meta {
                    description
                    errorCode
                    isHidden
                    template
                    title
                    validations
                    deviceConfig {
                      title
                      icon
                      defaultValues
                      rateConfig {
                        active
                        name
                        paperRollCharges
                        advancedRentalMonths
                        rentalDiscountMonths
                        plans {
                          oneTimeCharge
                          planDisplayName
                          planName
                          rentalCharge
                          setupFee
                        }
                      }
                    }
                    metaUi {
                      fields {
                        name
                        value
                      }
                    }
                  }
                  progress
                  status
                  name
                }
              }
              ... on ModularOnboardingStepWithSteps {
                __typename
                name
                progress
                status
                steps {
                  name
                  progress
                  status
                }
              }
            }
          }
        }
        onboardingState {
          milestones
          modularComponents
          steps
        }
        countryCode
        onboardingType
        merchantType
      }
      ... on merchantModularOnboardingDetailsFailureResponse {
        __typename
        code
        success
        message
        meta
      }
    }
  }

  fragment ModularOnboardingFieldFragment on ModularOnboardingField {
    name
    isDisabled
    isRequired
    isHidden
    meta {
      title
      description
      defaultValue
      size
      accessibilityLabel
      hideOnReviewScreen
      dataType
      selectionType
      options {
        label
        value
      }
      validations
    }
    failureReason
    failureReasonType
  }
`;

export const UPDATE_MODULAR_CONFIG = gql`
  mutation MerchantModularOnboardingDetailsUpdateAsSales(
    $merchantId: String!
    $fieldData: JSONObject
  ) {
    merchantModularOnboardingDetailsUpdateAsSales(merchantId: $merchantId, fieldData: $fieldData) {
      ... on merchantModularOnboardingDetailsSuccessResponse {
        __typename
        success
        workflowData {
          id
          progress
          status
          milestones {
            canSubmit
            name
            status
            steps {
              ... on ModularOnboardingStepInterface {
                __typename
                name
                progress
                status
              }
              ... on ModularOnboardingStepWithModularComponents {
                __typename
                name
                progress
                status
                meta {
                  title
                  description
                }
                modularComponents {
                  fields {
                    ... on ModularOnboardingFieldWithStringValue {
                      ...ModularOnboardingFieldFragment
                      stringValue: value
                    }
                    ... on ModularOnboardingFieldWithStringArrayValue {
                      ...ModularOnboardingFieldFragment
                      stringArrayValue: value
                    }
                    ... on ModularOnboardingFieldForDocumentUpload {
                      ...ModularOnboardingFieldFragment
                      documentUploadValue: value {
                        fileId
                        fileStoreId
                        name
                        size
                      }
                    }

                    ... on ModularOnboardingFieldForArrayOfDocumentsUpload {
                      ...ModularOnboardingFieldFragment
                      arrayOfDocumentsUploadValue: value {
                        name
                        size
                        fileStoreId
                      }
                    }
                    ... on ModularOnboardingFieldForDeviceCharges {
                      ...ModularOnboardingFieldFragment
                      orderSummary: value {
                        advanceRentalCharge
                        deviceCharge
                        gst
                        orderId
                        paperRollCharge
                        shippingCharge
                        totalOrderCharge
                        totalRentalCharge
                        rentalCharge {
                          deviceName
                          fee
                          gst
                          renewal
                        }
                      }
                    }
                    ... on ModularOnboardingFieldForOrderSummaryItem {
                      ...ModularOnboardingFieldFragment
                      addedDevices: value {
                        deviceName
                        itemId
                        paperRollCharge
                        paperRollQuantity
                        quantity
                        renewal
                        rentalCharge
                        setupCharge
                        totalAdvanceRentalCharge
                        totalPaperRollCharge
                        totalRentalCharge
                        totalSetupCharge
                        rentalChargeType
                        setupChargeType
                      }
                    }

                    ... on ModularOnboardingFieldWithBooleanValue {
                      ...ModularOnboardingFieldFragment
                      booleanValue: value
                    }

                    ... on ModularOnboardingFieldForJsonValues {
                      ...ModularOnboardingFieldFragment
                      jsonValue: value
                    }
                  }
                  meta {
                    description
                    errorCode
                    isHidden
                    template
                    title
                    validations
                    deviceConfig {
                      title
                      icon
                      defaultValues
                      rateConfig {
                        active
                        name
                        paperRollCharges
                        advancedRentalMonths
                        rentalDiscountMonths
                        plans {
                          oneTimeCharge
                          planDisplayName
                          planName
                          rentalCharge
                          setupFee
                        }
                      }
                    }
                    metaUi {
                      fields {
                        name
                        value
                      }
                    }
                  }
                  progress
                  status
                  name
                }
              }
              ... on ModularOnboardingStepWithSteps {
                __typename
                name
                progress
                status
                steps {
                  name
                  progress
                  status
                }
              }
            }
          }
        }
        onboardingState {
          milestones
          modularComponents
          steps
        }
        countryCode
        onboardingType
        merchantType
      }
      ... on merchantModularOnboardingDetailsFailureResponse {
        __typename
        code
        success
        message
        meta
      }
    }
  }

  fragment ModularOnboardingFieldFragment on ModularOnboardingField {
    name
    isDisabled
    isRequired
    isHidden
    meta {
      title
      description
      defaultValue
      size
      accessibilityLabel
      hideOnReviewScreen
      dataType
      selectionType
      options {
        label
        value
      }
      validations
    }
    failureReason
    failureReasonType
  }
`;

export const MERCHANT_DETAILS = gql`
  query MerchantById($id: ID!) {
    merchantById(id: $id) {
      createdAt
      id
      activation {
        posActivationStatus
        posActivationFlow
        status
        isFormSubmitted
        milestone
      }
      name {
        display
      }
      contactPerson {
        name {
          value
        }
        email {
          value
        }
        phone {
          value {
            number
          }
        }
      }

      business {
        address {
          registered {
            city {
              value
            }
            country {
              value
            }
            district {
              value
            }
            line1 {
              value
            }
            line2 {
              value
            }
            state {
              value
            }
            zipCode {
              value
            }
          }

          operation {
            city {
              value
            }
            country {
              value
            }
            district {
              value
            }
            line1 {
              value
            }
          }
        }
        paymentAcceptanceChannels {
          websites {
            urls {
              value
            }
            accept
            complianceConsent
          }
          ios {
            urls {
              value
            }
            accept
          }
          android {
            urls {
              value
            }
            accept
          }
          offlineStore {
            accept
          }
          socialMedia {
            accept
            socialMediaUrls {
              url
              platform
            }
          }
          whatsappSmsEmail {
            accept
          }
          others {
            accept
            value
          }
        }
      }
      document {
        shopFront {
          values {
            id
            fileName
          }
        }
        shopInterior {
          values {
            id
            fileName
          }
        }
      }
    }
  }
`;
