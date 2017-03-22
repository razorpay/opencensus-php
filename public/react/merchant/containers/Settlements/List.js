import React, { Component } from 'react'
import { connect } from 'react-redux'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import Header from 'rzp/ui/Header'
import ListContainer from 'merchant/containers/ListContainer'
import SettlementsList from 'merchant/components/Settlements/List'
import SettlementsListFilter from 'merchant/components/Settlements/ListFilter'
import SettlementBreakupModal from './BreakupModal'
import { fetchSettlements } from 'merchant/modules/settlements/list'
import * as ModalActions from 'merchant/modules/modals'

@connect(
  (state) => state.settlements,
  { fetchSettlements, ...ModalActions }
)
export default class SettlementsListContainer extends ListContainer {
  constructor() {
    super(...arguments)
    this.showBreakup = ::this.showBreakup
  }

  fetchEntityList(params) {
    return this.props.fetchSettlements(params)
  }

  showBreakup(settlement) {
    this.props.openModal({
      component: <SettlementBreakupModal settlementId={settlement.id} />
    })
  }

  render() {
    let { loading, settlements, error } = this.props

    return (
      <div class='react-root'>
        <Header title='Settlements' />

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <div class='panel-heading'>
              Settlements List
            </div>

            <div class='panel-body'>
              <SettlementsListFilter
                form='settlementsListFilter'
                count={this.state.count}
                onSubmit={this.search}
              />
            </div>

            {
              error &&
                <Alert
                  type='error'
                  message={error}
                />
            }

            <SettlementsList
              settlements={settlements}
              isLoading={loading}
              showBreakup={this.showBreakup}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={settlements.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    )
  }
}
