import React from 'react';

import debounce from 'rzp/utils/debounce';

import TypeAhead from 'rzp/ui/Select/TypeAhead';

export default class SearchbleTypeAhead extends React.Component {
  static getDerivedStateFromProps(nextProps, state) {
    if (nextProps.options) {
      const cache = {};

      [...nextProps.options, ...state.options].forEach(e => {
        cache[e.id] = e;
      });

      return {
        ...state,
        options: Object.values(cache),
      };
    }

    return null;
  }

  constructor(props) {
    super(props);

    this.state = {
      options: props.options || [],
    };

    this.debounceSearch = debounce(this.onSearch, 50);
  }

  onSearch = searchTerm => {
    this.props.searchMethod(searchTerm).then(res => {
      this.setState({
        options: res.data.items,
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
        options={this.state.options}
        onKeyDown={this.handleKeyDown}
      />
    );
  }
}
