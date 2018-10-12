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
    const { name, value } = target;
    console.log('NAME...', name);

    this.props.updateData({
      [name]: value,
    });
  }

  updateData = this.updateData.bind(this);

  render() {
    const paymentPageEntity = this.props.paymentPageEntity;
    const self = this;

    return (
      <React.Fragment>
        <div id="description-details">
          <Title title={paymentPageEntity.title} updateData={this.updateData} />
          <Description
            description={paymentPageEntity.description}
            updateData={this.updateData}
          />
        </div>

        <Share
          socialshare={paymentPageEntity.social_share}
          updateData={this.updateData}
        />

        <Support
          support={paymentPageEntity.support}
          updateData={this.updateData}
        />

        <Terms terms={paymentPageEntity.terms} updateData={this.updateData} />
      </React.Fragment>
    );
  }
}
