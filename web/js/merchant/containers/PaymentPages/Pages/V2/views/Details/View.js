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
    // console.log('NAME...', name);

    if (target.type === 'checkbox') {
      value = target.checked | 0; // Convert to 1 / 0
    }

    const dataToUpdate = {
      [name]: value,
    };

    if (['allow_social_share'].indexOf(target.name) > -1) {
      // Fields with settings
      this.props.updateData({ settings: { ...dataToUpdate } });
    } else {
      this.props.updateData(dataToUpdate);
    }
  }

  updateData = this.updateData.bind(this);

  render() {
    const { paymentPageEntity } = this.props;
    const self = this;

    if (!paymentPageEntity) {
      return null;
    }

    if (
      paymentPageEntity.id &&
      typeof paymentPageEntity.title === 'undefined'
    ) {
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
            key={
              paymentPageEntity.id ? paymentPageEntity.id + '-title' : 'title'
            }
            updateData={this.updateData}
          />
          <Description
            description={paymentPageEntity.description}
            key={
              paymentPageEntity.id
                ? paymentPageEntity.id + '-description'
                : 'description'
            }
            updateData={this.updateData}
          />
        </div>

        <Share
          allowSocialShare={settings.allow_social_share}
          updateData={this.updateData}
        />

        <Support
          support_contact={paymentPageEntity.support_contact}
          support_email={paymentPageEntity.support_email}
          updateData={this.updateData}
        />

        <Terms terms={paymentPageEntity.terms} updateData={this.updateData} />
      </React.Fragment>
    );
  }
}
