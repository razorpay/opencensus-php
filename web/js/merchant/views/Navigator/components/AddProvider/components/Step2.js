import Input from 'common/new-ui/Input';

export const Step2 = ({ steps, provider, changeProviderDetails, isProviderNameValid }) => {
  return (
    <div class="row">
      <div className="col-xs-12">
        <div className="row">
          <div className="col-xs-2">
            <label for="name" className="title-left">
              Provider Name
            </label>
          </div>
          <div className="col-xs-6">
            {!steps[2].edit ? (
              <label className="provider-details-read-only">{provider.Provider_name}</label>
            ) : (
              <Input
                id="name"
                class="Input--vLeft"
                name="name"
                value={provider.Provider_name}
                placeholder="Provider Name"
                onChange={(e) => changeProviderDetails('Provider_name', e.target.value)}
              />
            )}
          </div>
        </div>
      </div>
      {!isProviderNameValid && (
        <div className="col-xs-12">
          <div className="row">
            <div className="col-xs-2" />
            <div className="col-xs-10">
              <div className="provider-details-validation-error">
                You already have a payment provider with {` "${provider.Provider_name}". `}
                Please choose a different name.
              </div>
            </div>
          </div>
        </div>
      )}
      <div className="col-xs-12">
        <div className="row description-row">
          <div className="col-xs-2">
            <label for="description" className="title-left">
              Description
            </label>
          </div>
          <div className="col-xs-6">
            {!steps[2].edit ? (
              <label className="provider-details-read-only">{provider.Description}</label>
            ) : (
              <textarea
                id="description"
                class="Input--vLeft form-control"
                value={provider.Description}
                name="Description"
                placeholder="Description"
                onChange={(e) => changeProviderDetails('Description', e.target.value)}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
