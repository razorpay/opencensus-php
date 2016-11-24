import { Component } from 'react'

export default class ListGroupToggler extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      show: false
    }
    this.toggle = ::this.toggle
  }

  toggle() {
    this.setState({
      show: !this.state.show
    })
  }

  render() {
    return (
      <div class='list-group-item'>
        <button class='btn btn-xs btn-default pull-right' onClick={this.toggle}>Show/Hide</button>
        {this.props.label}
        {
          this.state.show ?
          <div class='panel-body'>
            <div class='list-group'>
              {this.props.children}
            </div>
          </div> : null
        }
      </div>
    )
  }
}
