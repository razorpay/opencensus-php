export interface CarouselWidgetProps {
  type: string;
  title: string;
  background_img?: string;
  components: Array<{ type: string; id: string; styles?: Record<string, any> }>;
}
