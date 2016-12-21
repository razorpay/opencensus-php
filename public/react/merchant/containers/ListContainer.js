import { PropTypes } from 'react'
import ModalContainer from 'merchant/containers/ModalContainer'

// Extending `ModalContainer` will be removed once modal management is moved to app's state
export default class ListContainer extends ModalContainer {
  static SKIP = 0
  static COUNT = 25
  static contextTypes = {
    ngRouter: PropTypes.object,
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state.status = {}
    this.search = ::this.search
    this.fetchAll = ::this.fetchAll
  }

  componentWillMount() {
    this.fetchAll()
  }

  fetchAll(params = this.getDefaultPageParams()) {
    if (params) {
      this.setState(params)
    }

    return this.fetchEntityList(params).then(() => {
      this.setState({
        status: {
          type: 'success',
          message: null
        }
      })
    }).catch((err) => {
      this.setState({
        status: {
          type: 'error',
          message: err.errors || err
        }
      })
    })
  }

  search(params) {
    return this.fetchAll({
      ...this.getDefaultPageParams(),
      ...params
    })
  }

  getDefaultPageParams() {
    return {
      skip: ListContainer.SKIP,
      count: ListContainer.COUNT
    }
  }

  fetchEntityList() {
    throw new Error(`Implement \`fetchEntityList\` func in the ${this.constructor.name} component`)
  }
}
