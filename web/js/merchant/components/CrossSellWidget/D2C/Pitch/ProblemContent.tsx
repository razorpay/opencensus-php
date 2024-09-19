import React from 'react';
import ContentTransitionWrapper from '../../ContentTransitionWrapper';
import ProductInsight from '../../ProductInsight';

function ProblemContent({
  pitchingProblemComponent,
  selectedViewId,
  pitchingProblemCrossSellWidgetData,
  actionClickHandler,
}): JSX.Element {
  return (
    <ContentTransitionWrapper id={pitchingProblemComponent.id} selectedViewId={selectedViewId}>
      <ProductInsight
        aboveDisplayHeading={pitchingProblemCrossSellWidgetData.text_content.above_display_heading}
        displayText={pitchingProblemCrossSellWidgetData.text_content.display}
        displayColor={pitchingProblemCrossSellWidgetData.text_content?.display_color}
        belowDisplayHeading={pitchingProblemCrossSellWidgetData.text_content.below_display_heading}
        bodyText={pitchingProblemCrossSellWidgetData.text_body}
        actionCta={pitchingProblemCrossSellWidgetData.actions.title}
        actionClickHandler={actionClickHandler}
      />
    </ContentTransitionWrapper>
  );
}

export default ProblemContent;
