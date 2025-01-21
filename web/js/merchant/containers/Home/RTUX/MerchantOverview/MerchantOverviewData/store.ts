import create from 'zustand';

type SettlementHovered = {
  isHovered: boolean;
  setIsHovered: (isHovered: boolean) => void;
};

type FohTicket = {
  fohTicketId: string;
  setFohTicketId: (fohTicketId: string) => void;
  fohTicketStatus: string;
  setFohTicketStatus: (fohTicketStatus: string) => void;
};

export const useIsSettlementHovered = create<SettlementHovered>((set) => ({
  isHovered: false,
  setIsHovered: (isHovered: boolean) => set({ isHovered }),
}));

export const useFohTicket = create<FohTicket>((set) => ({
  fohTicketId: '',
  setFohTicketId: (fohTicketId: string) => set({ fohTicketId }),
  fohTicketStatus: '',
  setFohTicketStatus: (fohTicketStatus: string) => set({ fohTicketStatus }),
}));
