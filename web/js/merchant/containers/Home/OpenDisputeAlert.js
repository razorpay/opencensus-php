import { Component } from 'react';
import { connect } from 'react-redux';

import { Link } from 'react-router-dom';
import Banner from 'common/ui/Banner';

// NOTE: this component is not being used currently but will be used in future
import { fetchOpen as fetchOpenDisputes } from 'merchant/reducers/disputes/details';

@connect(
  state => ({
    openDisputes: state.dispute.openDisputes,
  }),
  { fetchOpenDisputes }
)
export default class OpenDisputeAlert extends Component {
  state = { open: true };

  UNSAFE_componentWillMount() {
    /* disabling fetching for a while */
    // this.props.fetchOpenDisputes();
  }

  handleClose = () => {
    this.setState({ open: false });
  };

  render() {
    let { openDisputes, customClass } = this.props;
    return (
      this.state.open && (
        /* disabling this check for a while */
        // openDisputes > 0 && (
        <div class={`open-dispute-banner ${customClass || ''}`}>
          <Banner>
            {/* disabling this message for a while */}
            {/*There {openDisputes > 1 ? 'are' : 'is'} {openDisputes} open dispute{openDisputes >
              1 && 's'}{' '}
            against {openDisputes < 2 && 'a'} payment{openDisputes > 1 && 's'}&nbsp;
            that needs your attention. &nbsp;<Link to="/disputes">
              Show Disputes
            </Link>
            */}
            <span class="icon i-info-outline" />&nbsp; You can now view all your
            disputes on the dashboard. <Link to="/disputes">Show Disputes</Link>
            <i class="i i-close pull-right" onClick={this.handleClose} />
          </Banner>
        </div>
      )
    );
  }
}
