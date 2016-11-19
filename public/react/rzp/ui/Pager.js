import { Component } from 'react'

export default class Pager extends Component {
  constructor() {
    super(...arguments)
    this.onNext = ::this.onNext
    this.onPrev = ::this.onPrev
  }

  onNext() {
    let newParams = {
      skip: this.props.skip + this.props.count,
      count: this.props.count
    }
    this.props.onClick(newParams)
  }

  onPrev() {
    let newParams = {
      skip: this.props.skip - this.props.count,
      count: this.props.count
    }
    this.props.onClick(newParams)
  }

  render() {
    let { count, skip, length, onClick } = this.props
    let nextDisabled = length < count
    let prevDisabled = !skip

    return (
      <div class='clearfix' style={{
        margin: '20px'
      }}>
        {
          length ?
          <div class='btn-group pull-right'>
            <button
              type='button'
              class='btn btn-default btn-sm fa fa-chevron-left'
              disabled={prevDisabled}
              onClick={this.onPrev}
            ></button>
            <button
              type='button'
              class='btn btn-default btn-sm fa fa-chevron-right'
              disabled={nextDisabled}
              onClick={this.onNext}
            ></button>
          </div> : null
        }
      </div>
    )
  }
}
