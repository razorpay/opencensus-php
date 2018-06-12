import { Children, Component } from 'react';

import Section from './Section';

export default class Accordian extends Component {
  state = {
    currentStep: this.props.currentStep || 0,
  };

  handleChange = e => {
    const index = parseInt(e.target.dataset.id);
    const { currentStep } = this.state;

    if (currentStep === index) {
      this.setState({ currentStep: null });
      return;
    }

    this.setState({ currentStep: index });
  };

  render() {
    const { currentStep } = this.state;
    const { children } = this.props;

    return (
      <div class="Accordian">
        {Children.map(children, (child, index) => (
          <Section
            key={index}
            index={index}
            onChange={this.handleChange}
            isOpen={index === currentStep}
          >
            {child}
          </Section>
        ))}
      </div>
    );
  }
}
