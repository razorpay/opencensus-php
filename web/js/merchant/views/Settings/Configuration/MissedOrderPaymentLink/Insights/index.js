import Amount from 'common/ui/Amount';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';

import Lock from 'assets/missed_order/lock.svg';
import { monthsMap } from 'common/utils/date-utils';
import { getPercentage, titleCase } from 'common/utils/rzp-utils';
import IconSave from 'assets/missed_order/icon-save.svg';
import WhatsApp from 'assets/app-store/partner-logos/whatsapp.png';
import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import Button from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const InfoWrapper = ({ value, title, children, tracking }) => {
  const trackConversionChannels = (eventType) => {
    const eventName =
      eventType === 'click'
        ? 'dashboard.settings.mopl.channel_click'
        : 'dashboard.settings.mopl.conversion_rate';
    tracking && track.insights.conversionChannel(eventName, { channel: title });
  };

  return (
    <div
      className="info-wrapper"
      onMouseEnter={() => trackConversionChannels('hover')}
      onClick={() => trackConversionChannels('click')}
    >
      <div className="heading">{value}</div>
      <div className="whatsapp-wrapper">
        {title.toLowerCase() === 'whatsapp' && <img src={WhatsApp} width="12px" height="12px" />}
        <span className="sub-heading">{title}</span>
      </div>
      {children}
    </div>
  );
};

const ViewInsight = ({ closeModal, isProPlan = false, insights }) => {
  const today = new Date();
  today.setDate(today.getDate() - 1); // we are not using moment here because of it's use size of 19kb (gzziped)

  return (
    <div className="mopl-insights-container">
      <ModalHeader title="Re-Marketer insights" onCloseClick={closeModal} />
      <div className="content">
        <div className="heading flex">
          <div>Showing insights for</div>
          <div className="blue-text">
            {monthsMap[today.getMonth()]} {today.getFullYear()}
          </div>
        </div>
        <div className="insight-highlighter flex">
          <div>
            <img className="insight-image-wrapper" src={IconSave} width="28px" height="27px" />
          </div>
          <div className="amount-wrapper">
            <Amount value={insights.revived_amount} currency="INR" /> saved by reviving failed
            orders
          </div>
        </div>
        <div>
          <div className="subtitle retarget-wrapper">Retarget attempts</div>
          <div className="flex info-space">
            <InfoWrapper value={insights.retarget_attempts} title="Total retargets" />
            <InfoWrapper value={insights.successful_payments} title="Successful payments" />
          </div>
        </div>
        <div className="conversion-wrapper">
          <div className="subtitle retarget-wrapper">Conversion rate by channels</div>
          {isProPlan ? (
            <div className="flex info-space">
              {insights.channel_conversions?.map((data) => {
                return (
                  <InfoWrapper
                    key={data.channel}
                    title={titleCase(data.channel)}
                    value={`${getPercentage(data.sent, data.paid)}%`}
                  >
                    {data.sent > 0 && data.paid && (
                      <Popover
                        align="bottom"
                        theme="dark"
                        parentQuerySelector=".mopl-insights-container"
                      >
                        {' '}
                        <PopoverBody>
                          <div
                            style={{
                              gap: '4px',
                              display: 'flex',
                              justifyContent: 'space-between',
                            }}
                          >
                            <div>
                              <div>TOTAL {data.channel.toUpperCase()} SENT</div>
                              <div style={{ width: '45%' }}>{data.sent}</div>
                            </div>
                            <div>
                              <div>PAID VIA {data.channel.toUpperCase()}</div>
                              <div style={{ width: '45%' }}>{data.paid}</div>
                            </div>
                          </div>
                        </PopoverBody>
                      </Popover>
                    )}
                  </InfoWrapper>
                );
              })}
            </div>
          ) : (
            <div id="pro-plan-lock-wrapper">
              <div className="img-wrapper">
                <img src={Lock} height="12px" width="12px" />
              </div>
              <div className="pro-plan-text">
                Get conversion insights on the <b>Pro plan</b>
              </div>
            </div>
          )}
        </div>
        <div className="btn-wrapper">
          <Button buttonText="Close" btnClassName="btn-block" onClick={closeModal} hideIcon />
        </div>
        <div className="info-data-validation">
          <i className="i i-info-outline" />
          <div>
            Data updated on {today.getDate()} {monthsMap[today.getMonth()]}, {today.getFullYear()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ViewInsight;
