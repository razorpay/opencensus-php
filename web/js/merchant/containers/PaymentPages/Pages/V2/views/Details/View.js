import { connect } from 'react-redux';
import { updateData } from 'merchant/modules/wysiwyg';
import Title from './Title';
import Description from './Description';
import Share from './Share';
import Support from './Support';
import Terms from './Terms';

@connect(state => ({ paymentPageEntity: state.wysiwyg.paymentPageEntity }), {
  updateData,
})
export default class View extends React.PureComponent {
  updateData({ target }) {
    let { name, value } = target;
    console.log('NAME...', name);

    if (target.type === 'checkbox') {
      value = target.checked | 0; // Convert to 1 / 0
    }

    this.props.updateData({
      [name]: value,
    });
  }

  updateData = this.updateData.bind(this);

  render() {
    const { paymentPageEntity } = this.props;
    const self = this;

    if (!paymentPageEntity) {
      return null;
    }

    if (paymentPageEntity.id && !paymentPageEntity.title) {
      return (
        <div class="spinner-container">
          <div class="spin-btn large visible" />
        </div>
      );
    }

    const settings = paymentPageEntity.settings || {};

    return (
      <React.Fragment>
        <div id="description-details">
          <Title
            title={paymentPageEntity.title}
            key={paymentPageEntity.id + '-title' || 'title'}
            updateData={this.updateData}
          />
          <Description
            description={paymentPageEntity.description}
            updateData={this.updateData}
          />
        </div>

        <Share
          allowSocialShare={settings.allow_social_share}
          updateData={this.updateData}
        />

        <Support
          support_phone={settings.support_phone}
          support_email={settings.support_email}
          updateData={this.updateData}
        />

        <Terms terms={settings.terms} updateData={this.updateData} />
      </React.Fragment>
    );
  }
}
