import Popover, { PopoverBody } from 'common/ui/Popover';

export default ({ value, popoverContent, required = false, errorText = '' }) => {
  const hasError = errorText.length > 0;
  return (
    <div className="settings-label-wrapper">
      <div className={`setting-label ${required ? 'required' : ''} ${errorText ? 'error' : ''}`}>
        {value}
        {popoverContent && (
          <i className="i i-info-outline cod-engine-tooltip font-normal">
            <Popover theme="dark">
              <PopoverBody>
                <p>{popoverContent}</p>
              </PopoverBody>
            </Popover>
          </i>
        )}
      </div>
      {hasError && <p className="error-text">{errorText}</p>}
    </div>
  );
};
