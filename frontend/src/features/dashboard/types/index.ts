export interface Author {
  name: string;
  avatar: { url: string };
  databaseId: number;
}

export interface Tag {
  label: string;
  color: string;
}

export interface Task {
  id: number;
  title: string;
  status: string;
  tags: Tag[];
  comments: number;
  date: string;
  author: Author;
}

export interface ColumnConfig {
  slug: string;
  label: string;
  bgStyle: string;
  textStyle: string;
}