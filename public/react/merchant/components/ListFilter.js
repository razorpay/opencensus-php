import { Component } from 'react'
import { reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'

@reduxForm()
export default class ListFilter extends Component {
  componentWillMount() {
    let count = this.props.count
    if (count) {
      this.props.initialize({
        count
      })
    }
  }

  render() {
    let { handleSubmit, onSubmit, reset } = this.props
    return (
      <form onSubmit={handleSubmit(onSubmit)} class='list-filter-container'>
        {this.props.children}
        <div class='form-group list-filter-item btn-group'>
          <AsyncButton
            class='btn btn-sm btn-default'
            onClick={handleSubmit(onSubmit)}
            text='Search'
          />
          <AsyncButton
            class='btn btn-sm btn-default'
            onClick={reset}
            text='Clear'
          />
        </div>
      </form>
    )
  }
}

ListFilter.defaultProps = {
  onSubmit: () => {}
}
