import React from 'react';

import ContentTransitionWrapper from '../../ContentTransitionWrapper';
import ProductInsight from '../../ProductInsight';

function SolutionContent({
  pitchingSolutionComponent,
  selectedViewId,
  pitchingSolutionCrossSellWidgetData,
  actionClickHandler,
}) {
  return (
    <ContentTransitionWrapper id={pitchingSolutionComponent.id} selectedViewId={selectedViewId}>
      <ProductInsight
        badges={pitchingSolutionCrossSellWidgetData.badges}
        logoImages={pitchingSolutionCrossSellWidgetData.brand_images}
        aboveDisplayHeading={pitchingSolutionCrossSellWidgetData.text_content.above_display_heading}
        displayText={pitchingSolutionCrossSellWidgetData.text_content.display}
        displayColor={pitchingSolutionCrossSellWidgetData.text_content?.display_color}
        belowDisplayHeading={pitchingSolutionCrossSellWidgetData.text_content.below_display_heading}
        bodyText={pitchingSolutionCrossSellWidgetData.text_body}
        actionCta={pitchingSolutionCrossSellWidgetData.actions.title}
        captionList={pitchingSolutionCrossSellWidgetData.text_content.caption_list}
        actionClickHandler={actionClickHandler}
      />
    </ContentTransitionWrapper>
  );
}

export default SolutionContent;
