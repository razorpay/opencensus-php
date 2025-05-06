import React, { useState, useEffect } from 'react';
import { Box } from '@razorpay/blade/components';

import { merchantFetch } from 'merchant/utils/ajax';
import LoaderModal from 'merchant/views/Settings/PaymentMethods/components/Modals/LoaderModal';
import TermsAndCondition from 'merchant/views/Settings/PaymentMethods/components/Modals/TermsAndCondition';
import { WEBSITE_FIELDS } from 'merchant/views/Settings/PaymentMethods/constants';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import {
  getAllFields,
  getVerifiedNames,
  updatedWebsiteDetailsValues,
  trackScraperModal,
} from './utils';
import WebsiteDetailsModal from './websiteDetail';
import { bindActionCreators, compose } from 'redux';

const ScrapperModal = ({ isOpen, handleModal, showNotification, props }) => {
  const [loading, setLoading] = useState(false);
  const [apiLoading, setApiLoading] = useState(false);
  const [isScrapperFormView, setScrapperFormView] = useState(false);
  const [collectInfo, setCollectInfo] = useState([]);
  const [retries, setRetries] = useState(0);

  useEffect(() => {
    if (isOpen) {
      trackScraperModal('Website Scraper Modal', 'opened', props);
    }
  }, [isOpen, props]);

  function pollApi(pullTime, timeout = 70000) {
    return new Promise((resolve, reject) => {
      const controller = new AbortController();
      let isPolling = true;
      const timeoutId = setTimeout(() => {
        controller.abort();
        isPolling = false;
        trackScraperModal('Website Scraper Polling', 'timed_out', props, {
          timeout_ms: timeout,
        });
        reject(new Error('Polling timed out after 1 minute'));
      }, timeout);

      async function makeRequest() {
        if (!isPolling) return;
        try {
          const data = await merchantFetch('collect_info/website_details/scraper/status', {
            signal: controller.signal,
          });
          if (data?.success) {
            if (data?.data?.status === 'success' || data?.data?.status === 'completed') {
              clearTimeout(timeoutId);
              trackScraperModal('Website Scraper Polling', 'completed', props, {
                status: data?.data?.status,
              });
              resolve(data);
              return;
            } else if (data?.data?.status === 'failed') {
              clearTimeout(timeoutId);
              trackScraperModal('Website Scraper Polling', 'failed', props, {
                status: data?.data?.status,
              });
              reject(new Error('Failed to visit your website'));
              return;
            }
          } else {
            clearTimeout(timeoutId);
            trackScraperModal('Website Scraper Polling', 'api_error', props, {
              errors: data?.errors,
            });
            reject(data?.errors);
            return;
          }
        } catch (error) {
          if (error.name === 'AbortError') {
            return;
          }
          clearTimeout(timeoutId);
          trackScraperModal('Website Scraper Polling', 'error', props, {
            error_message: error.message,
          });
          reject(error);
          return;
        }
        setTimeout(makeRequest, pullTime);
      }

      makeRequest();
    });
  }

  const handleAcceptTermsAndConditions = async () => {
    const { instrument } = props;
    const hasWebsiteDetails = instrument?.collect_info.some(
      (field) => field.category === 'Website Details',
    );

    trackScraperModal('Website Scraper Terms', 'accepted', props, {
      has_website_details: hasWebsiteDetails,
    });

    if (!hasWebsiteDetails) {
      setCollectInfo(instrument?.collect_info);
      setScrapperFormView(true);
      return;
    }
    setLoading(true);

    try {
      const triggerResponse = await merchantFetch({
        url: 'collect_info/website_details/scraper',
        method: 'post',
        data: { instrument_name: instrument?.path },
      });

      if (!triggerResponse?.success) {
        trackScraperModal('Website Scraper Trigger', 'failed', props, {
          errors: triggerResponse?.errors,
        });

        showNotification({ type: 'error', message: 'Failed to trigger web-scraper' });
        const updatedInfo = updatedWebsiteDetailsValues(instrument?.collect_info, []);
        setCollectInfo(updatedInfo);
        setScrapperFormView(true);
        return;
      }

      trackScraperModal('Website Scraper Trigger', 'success', props, {
        pull_time_ms: triggerResponse?.data?.data?.pull_time_milliseconds,
      });

      const pullTimeMilliseconds = triggerResponse?.data?.data?.pull_time_milliseconds ?? null;
      const pollInterval = pullTimeMilliseconds || 2000;
      const webScraperPayload = await pollApi(pollInterval);

      if (!webScraperPayload) {
        showNotification({ type: 'error', message: 'Failed during polling web-scraper' });
        const updatedInfo = updatedWebsiteDetailsValues(instrument?.collect_info, []);
        setCollectInfo(updatedInfo);
        setScrapperFormView(true);
        return;
      }

      const updatedInfo = updatedWebsiteDetailsValues(
        instrument?.collect_info,
        webScraperPayload?.data?.website_details,
      );
      setCollectInfo(updatedInfo);
      setScrapperFormView(true);
    } catch (error) {
      const { instrument } = props;
      const updatedInfo = updatedWebsiteDetailsValues(instrument?.collect_info, []);
      setCollectInfo(updatedInfo);
      setScrapperFormView(true);
      showNotification({
        type: 'error',
        message:
          'Unable to verify website automatically. Please provide the direct URL or check if the page is accessible.',
      });
    } finally {
      setLoading(false);
    }
  };

  const saveMerchantDetails = (data) => {
    trackScraperModal('Website Scraper Form', 'submitted', props, {
      verified_fields_count: data?.verified_fields?.length || 0,
    });

    return merchantFetch({
      url: `terminals/proxy/collect_info/merchant/details`,
      method: 'post',
      data,
    })
      .then((d) => {
        if (d?.success) {
          trackScraperModal('Website Scraper Form', 'submission_success', props);

          setRetries(0);
          handleModal(true);
          setScrapperFormView(false);
        }
        return false;
      })
      .catch((error) => {
        trackScraperModal('Website Scraper Form', 'submission_error', props, {
          error: JSON.stringify(error?.errors),
        });

        showNotification({ type: 'error', message: JSON.stringify(error?.errors) });
      })
      .finally(() => {
        setApiLoading(false);
      });
  };
  const handleSubmitRequest = () => {
    const verifiedFields = getVerifiedNames(collectInfo);
    const allFields = getAllFields(collectInfo);
    setApiLoading(true);
    const payload = {
      instruments: [props.instrument?.path],
      ...(verifiedFields?.length && { verified_fields: verifiedFields }),
      ...allFields,
    };

    saveMerchantDetails(payload);
  };

  const verifyWebscrapper = async (payload) => {
    setLoading(true);
    setRetries(retries + 1);

    trackScraperModal('Website Scraper Verification', 'attempted', props, {
      retry_count: retries + 1,
    });

    try {
      const trigger = await merchantFetch({
        url: 'collect_info/website_details/verify_scraper',
        method: 'post',
        data: payload,
      });
      if (trigger.success) {
        const webScrapperPayload = await pollApi(trigger?.pull_time_milliseconds || 2000);
        if (webScrapperPayload.success) {
          const data = webScrapperPayload.data;
          const collectInfoGrouped = updatedWebsiteDetailsValues(collectInfo, data.website_details);
          setCollectInfo(collectInfoGrouped);

          trackScraperModal('Website Scraper Verification', 'success', props, {
            retry_count: retries + 1,
          });
        } else {
          throw new Error();
        }
      }
    } catch (error) {
      trackScraperModal('Website Scraper Verification', 'error', props, {
        retry_count: retries + 1,
        error_message: error.message,
      });

      showNotification({
        type: 'error',
        message:
          'Unable to verify website automatically. Please provide the direct URL or check if the page is accessible.',
      });
    } finally {
      setScrapperFormView(true);
      setLoading(false);
      setApiLoading(false);
    }
  };

  const handleClose = () => {
    trackScraperModal('Website Scraper Modal', 'closed', props, {
      is_scrapper_form_view: isScrapperFormView,
    });

    setRetries(0);
    setScrapperFormView(false);
    setLoading(false);
    handleModal(false);
  };

  const handleRetryClick = () => {
    trackScraperModal('Website Scraper Retry', 'clicked', props, {
      retry_count: retries,
    });

    setApiLoading(true);
    const website_details = collectInfo
      .filter((item) => WEBSITE_FIELDS.includes(item.name))
      .map((item) => {
        return {
          name: item.name,
          value: item.value,
          verification_status: item.verification_status,
        };
      });
    const payload = {
      instrument_name: props.instrument_name,
      website_details,
    };
    verifyWebscrapper(payload);
  };
  const handleWebsiteFieldChange = (name, value) => {
    trackScraperModal('Website Scraper Field', 'changed', props, {
      field_name: name,
    });

    setCollectInfo(
      collectInfo.map((item) => {
        if (item.name === name) {
          item.value = value.trim();
        }
        return item;
      }),
    );
  };
  if (loading) {
    return <LoaderModal isOpen={isOpen} handleClose={handleModal} />;
  }
  return (
    <Box>
      {isScrapperFormView ? (
        <WebsiteDetailsModal
          isOpen={isOpen}
          props={props}
          retries={retries}
          submitLoading={apiLoading}
          handleClose={handleClose}
          collectInfo={collectInfo}
          handleRetryClick={handleRetryClick}
          handleSubmitRequest={handleSubmitRequest}
          handleWebsiteFieldChange={handleWebsiteFieldChange}
        />
      ) : (
        <TermsAndCondition
          props={props}
          isOpen={isOpen}
          handleClose={handleModal}
          handleAcceptTermsAndConditions={handleAcceptTermsAndConditions}
        />
      )}
    </Box>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ showNotification: showNotificationReducer }, dispatch);

export default compose(connect(null, mapDispatchToProps))(ScrapperModal);
