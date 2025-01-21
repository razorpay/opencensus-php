export type DraggableProps = {
  id: string;
  name: string;
  isSelected: boolean;
  mandatoryColumns: string[];
  onMandatoryColumDeselection: () => void;
  toggleColumnSelection: (isSelected: boolean, id: string) => void;
};
