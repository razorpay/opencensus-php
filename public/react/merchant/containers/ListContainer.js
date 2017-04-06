import { Component, PropTypes } from 'react'

export default class ListContainer extends Component {
  static SKIP = 0
  static COUNT = 25
  static contextTypes = {
    ngRouter: PropTypes.object,
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state = {
      status: {}
    }
  }

  componentWillMount() {
    this.fetchAll()
  }

  fetchAll = (params = this.getDefaultPageParams()) => {
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

  search = (params) => {
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
