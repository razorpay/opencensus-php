import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TransfersListFilter from 'merchantLA/components/Marketplace/TransfersListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchTransfers as fetchAll } from 'merchantLA/modules/collection';
import { transferId, amount, createdAt } from 'merchantLA/utils/item/pair';
import { classList } from 'common/util';
import Amount from 'rzp/ui/Amount';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

const helperCues = {
  title: '',
  value: item => {
    const hasReversals = item.amount_reversed > 0;

    let notesMsg = 'No Notes';
    const notesLength = item.notes && Object.keys(item.notes).length;

    if (notesLength) {
      if (notesLength === 1) {
        notesMsg = notesLength + ' Note attached';
      } else {
        notesMsg = notesLength + ' Notes attached';
      }
    }

    return (
      <div style={{ color: 'green' }}>
        <span>
          <i
            class={classList(
              'i i-reversal cue',
              hasReversals ? 'cue--active' : 'cue--inactive'
            )}
          />
          <Popover align="top">
            <PopoverBody>
              <div>
                {hasReversals ? (
                  <span>
                    <Amount value={item.amount_reversed} /> amount reversed
                  </span>
                ) : (
                  'No Reversals'
                )}
              </div>
            </PopoverBody>
          </Popover>
        </span>

        <span>
          <i
            class={classList(
              'i i-notes cue',
              notesLength ? 'cue--active' : 'cue--inactive'
            )}
          />
          <Popover align="top">
            <PopoverBody>
              <div>{notesMsg}</div>
            </PopoverBody>
          </Popover>
        </span>
      </div>
    );
  },
};

@connect(state => state.transfers, { fetchAll })
export default class TransfersListContainer extends ListContainer {
  render() {
    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/transfers">Transfers</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <TransfersListFilter
                form="transfersListFilter"
                count={this.state.count}
                onSubmit={this.search}
              />

              <DataTable
                title="Transfers"
                columns={[transferId, amount, createdAt, helperCues]}
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                {...this.props}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
