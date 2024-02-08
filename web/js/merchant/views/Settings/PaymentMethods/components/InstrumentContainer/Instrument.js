import { useState } from 'react';
import {
  Button as AsyncButton,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import { getIcon } from 'merchant/views/Settings/PaymentMethods/components/InstrumentIcons';
import { REQUESTABLE, GREYED, REQUESTED } from 'merchant/views/Settings/PaymentMethods/constants';
/*
 * @param  {*} data = { icon, name, description, status }
 * @param  {*} showAction condition to show button for individual list item
 * @param  {*} rightButton custom component to replace button if needed
 * @param  {*} showTat bool to check whether tat needs to be shown or not
 */
const Instrument = ({
  data = {},
  showInstrumentAction,
  rightButton,
  onInstrumentRequest,
  leafInstrument,
  instrumentsTat,
  showTat = true,
  isRequestButtonDisabled = false,
  requestTooltipText,
}) => {
  const { icon, name, description, status = GREYED, slug = '' } = data;
  const tat = instrumentsTat?.[`pg.${leafInstrument?.slug ?? ''}.${slug}`];
  const Button = rightButton;

  const [isLoading, setIsLoading] = useState(false);

  const onButtonClick = async () => {
    setIsLoading(true);
    await onInstrumentRequest(data);
    setIsLoading(false);
  };

  return (
    <div className="instrument-row">
      {icon && <img src={getIcon(icon)} alt={name} />}
      <div className="text-wraper">
        {name ? <strong className="name">{name}</strong> : null}
        {description ? <p className="description">{description}</p> : null}
      </div>
      {showInstrumentAction && !rightButton ? (
        [REQUESTABLE, GREYED].includes(status) ? (
          <div className="request-cta">
            {requestTooltipText ? (
              <Tooltip content={requestTooltipText} position="top">
                <TooltipInteractiveWrapper width="100%">
                  <AsyncButton
                    variant="primary"
                    isLoading={isLoading}
                    size="small"
                    isFullWidth
                    onClick={onButtonClick}
                    isDisabled={isRequestButtonDisabled}
                  >
                    Request
                  </AsyncButton>
                </TooltipInteractiveWrapper>
              </Tooltip>
            ) : (
              <AsyncButton
                variant="primary"
                isLoading={isLoading}
                size="small"
                isFullWidth
                onClick={onButtonClick}
                isDisabled={isRequestButtonDisabled}
              >
                Request
              </AsyncButton>
            )}
          </div>
        ) : (
          <div className="status-wrapper">
            <InternationalStatusLabel status={status} />
            {status === REQUESTED && showTat && tat ? (
              <p className="tat-text">
                TAT: {'<'} {tat} Days
              </p>
            ) : null}
          </div>
        )
      ) : null}
      {showInstrumentAction && rightButton ? <Button /> : null}
    </div>
  );
};

export default Instrument;
