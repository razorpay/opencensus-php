import React from 'React';

import debounce from 'rzp/utils/debounce';

import TypeAhead from 'rzp/ui/Select/TypeAhead';

export default class _default extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      options: [],
    };

    this.debounceSearch = debounce(this.onSearch, 50);
  }

  onSearch = searchTerm => {
    this.props
      .searchMethod(searchTerm)
      .then(res => {
        this.setState({
          options: res.data.items,
          loading: false,
        });
      })
      .catch(e => {
        this.setState({
          loading: false,
        });
      });
  };

  handleKeyDown = e => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;

      if (val.length < 2) {
        this.setState({ options: [] });
        return;
      }

      this.debounceSearch(val);
    }, 5);
  };

  render() {
    const props = this.props;

    return (
      <TypeAhead
        {...props}
        options={this.props.options || this.state.options}
        onKeyDown={this.handleKeyDown}
      />
    );
  }
}
