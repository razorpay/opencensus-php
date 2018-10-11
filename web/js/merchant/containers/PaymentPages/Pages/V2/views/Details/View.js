import { connect } from 'react-redux';
import { updateData } from 'merchant/modules/wysiwyg';
import debounce from 'rzp/utils/debounce';

import Description from './Description';
import Share from './Share';
import Support from './Support';
import Terms from './Terms';

@connect(state => ({ paymentPageEntity: state.wysiwyg.paymentPageEntity }), {
  updateData,
})
export default class View extends React.PureComponent {
  onChange = ({ target }) => {
    const { name, value } = target;

    this.props.updateData({
      [name]: value,
    });
  };

  render() {
    const paymentPageEntity = this.props.paymentPageEntity;

    return (
      <React.Fragment>
        <Description
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          onChange={this.onChange}
        />

        <Share
          socialshare={paymentPageEntity.social_share}
          onChange={this.onChange}
        />

        <Support support={paymentPageEntity.support} onChange={this.onChange} />

        <Terms terms={paymentPageEntity.terms} onChange={this.onChange} />
      </React.Fragment>
    );
  }
}
