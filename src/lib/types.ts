export type Profile = {
  id: string;
  username: string;
  display_name: string;
  bio: string;
  avatar_url: string;
  website_url: string;
  github_url: string;
  role: 'member' | 'moderator' | 'admin';
};

export type Showcase = {
  id: string;
  owner_id: string;
  slug: string;
  title: string;
  headline: string;
  about: string;
  location: string;
  cover_url: string;
  accent: string;
  is_published: boolean;
  profile?: Profile | null;
};

export type Post = {
  id: string;
  showcase_id: string;
  author_id: string;
  kind: 'project' | 'article' | 'note';
  slug: string;
  title: string;
  excerpt: string;
  content: string;
  cover_url: string;
  tags: string[];
  status: 'draft' | 'published';
  featured: boolean;
  published_at: string | null;
  created_at: string;
  updated_at: string;
};
