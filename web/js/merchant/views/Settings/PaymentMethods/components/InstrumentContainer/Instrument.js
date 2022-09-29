import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import AsyncButton from 'react-async-button';
import { getIcon } from 'merchant/views/Settings/PaymentMethods/components/InstrumentIcons';
import { REQUESTABLE, GREYED, REQUESTED } from 'merchant/views/Settings/PaymentMethods/constants';

/*
 * @param  {*} data = { icon, name, description, status }
 * @param  {*} showAction condition to show button for individual list item
 * @param  {*} rightButton custom component to replace button if needed
 * @param  {*} showTat bool to check whether tat needs to be shown or not
 */
const Instrument = ({
  data,
  showAction,
  rightButton,
  onInstrumentRequest,
  leafInstrument,
  instrumentsTat,
  showTat = true,
}) => {
  const { icon, name, description, status, slug = '' } = data;
  const tat = instrumentsTat?.[`pg.${leafInstrument?.slug ?? ''}.${slug}`];
  const Button = rightButton;

  const onButtonClick = () => {
    return onInstrumentRequest(data);
  };

  return (
    <div className="instrument-row">
      <img src={getIcon(icon)} alt={name} />
      <div className="text-wraper">
        {name ? <strong className="name">{name}</strong> : null}
        {description ? <p className="description">{description}</p> : null}
      </div>
      {showAction && !rightButton ? (
        [REQUESTABLE, GREYED].includes(status) ? (
          <AsyncButton
            type="button"
            className="pull-right btn btn-primary"
            text="Request"
            pendingText="Requesting"
            onClick={onButtonClick}
          />
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
      {showAction && rightButton ? <Button /> : null}
    </div>
  );
};

export default Instrument;
