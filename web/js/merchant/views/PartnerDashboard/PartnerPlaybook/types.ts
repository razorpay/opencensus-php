import { CommonApiResponse } from 'common/typings';

export enum ProgramHeader {
  get_started = 'get_started',
  grow_your_business = 'grow_your_business',
  help_and_support = 'help_and_support',
}

export enum PlaybookContentTypes {
  PDF = 'PDF',
  VIDEO = 'VIDEO',
  PPT = 'PPT',
  DOC = 'DOC',
  IMAGE = 'IMAGE',
}

export type ProgramItem = {
  id: string;
  content_type: PlaybookContentTypes;
  title: string;
  description: string;
  copy_url: string;
  download_url: string;
  preview_url: string;
};

type ProgramFolderHeader = {
  count: number;
  description: string;
  title: string;
  total: number;
};

export type ProgramFolder = {
  header: null | ProgramFolderHeader;
  items: Array<ProgramItem>;
};

export type ProgramFolderStore = {
  header: null | Omit<ProgramFolderHeader, 'count'>;
  items: Array<Omit<ProgramItem, 'id'>>;
};

export type ProgramSection = {
  header: {
    count: number;
    icon: string;
    description: string;
    title: string;
    total: number;
    hash: string;
    pageFold: number;
  };
  folders: Array<ProgramFolder>;
};
type ProgramSectionStore = {
  header: Omit<ProgramSection['header'], 'count' | 'pageFold'>;
  folders: Array<ProgramFolderStore>;
};

export type PlaybookItems = Array<{
  sectionKey: ProgramHeader;
  sectionItem: ProgramSection;
}>;
export type PlaybookItemsStore = Array<{
  sectionKey: ProgramHeader;
  sectionItem: ProgramSectionStore;
}>;

export type PlaybookItemsStoreInitial = Array<{
  sectionKey: ProgramHeader;
  sectionItem: {
    header: Omit<ProgramSectionStore['header'], 'total'>;
    folders: Array<{
      header: null | Omit<ProgramFolderHeader, 'count' | 'total'>;
      items: ProgramSectionStore['folders'][0]['items'];
    }>;
  };
}>;

export type FetchPlaybookItemsResponse = CommonApiResponse<PlaybookItems>;

export type PlaybookFiltersType = {
  query: string;
};
