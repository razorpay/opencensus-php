import PreviewFormShell from '../PreviewFormShell';

export default class CustomerDetailsPreview extends React.Component {
  render() {
    const { udfFields } = this.props;

    return (
      <PreviewFormShell
        shellTitle="CUSTOMER DETAILS"
        buttonTitle="PROCEED TO PAY"
        totalDots={2}
        activeDotsIndex={0}
        {...this.props}
      >
        <div>
          {/* TODO: Check for asterisk/optional RazorX experiment */}
          {udfFields.map((field, index) => {
            return (
              <div class="Field--dummy Field--dummy--udf" key={index}>
                <div class="Field-label">{field.title}</div>
                {/* TODO: add dropdown icon for dropdown field */}
                <div class="Field-el" />
                <div class="Field-description">{field.description}</div>
              </div>
            );
          })}
        </div>
      </PreviewFormShell>
    );
  }
}
