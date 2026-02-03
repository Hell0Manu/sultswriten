import { useState, useMemo } from "react";
import { useQuery } from "@apollo/client/react";
import { GET_DASHBOARD_DATA } from "@/lib/queries";
import type { Task, ColumnConfig } from "@/features/dashboard/types";

// Lógica de permissão isolada
const canViewAllPosts = (roles: any[]) => {
  const adminRoles = ['administrator', 'editor_chefe']; 
  return roles?.some(r => adminRoles.includes(r.name)) || false;
};

export function useDashboardData() {
  const [searchTerm, setSearchTerm] = useState("");
  
  const { data, loading, error } = useQuery(GET_DASHBOARD_DATA, {
    fetchPolicy: "network-only",
  });

  const processedData = useMemo(() => {
    if (!data) return { kanbanTasks: [], publishedTasks: [], dynamicColumns: [] };

    const viewer = data.viewer;
    const isAdmin = viewer ? canViewAllPosts(viewer.roles.nodes) : false;
    const currentUserId = viewer?.databaseId;

    // Mapeamento dos posts
    const allPosts: Task[] = data.posts?.nodes?.map((post: any) => {
      let s = post.status ? post.status.toLowerCase() : 'draft';
      if (s === 'pending') s = 'review_in_progress'; 

      return {
        id: post.databaseId,
        title: post.title,
        status: s,
        tags: post.categories?.nodes.map((cat: any) => ({
          label: cat.name,
          color: "bg-indigo-500" 
        })) || [],
        comments: post.comments?.pageInfo?.total || 0,
        date: new Date(post.date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }),
        author: post.author?.node || { name: "Anon", avatar: { url: "" }, databaseId: 0 }
      };
    }) || [];

    // Filtro de permissão
    const visiblePosts = isAdmin 
      ? allPosts 
      : allPosts.filter(p => p.author.databaseId === currentUserId);

    return {
      kanbanTasks: visiblePosts.filter(t => t.status !== 'publish'),
      publishedTasks: visiblePosts.filter(t => t.status === 'publish'),
      dynamicColumns: (data.workflowStatuses || []) as ColumnConfig[]
    };
  }, [data]);

  // Filtro de busca
  const filteredKanban = processedData.kanbanTasks.filter(t => 
    t.title.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  const filteredPublished = processedData.publishedTasks.filter(t => 
    t.title.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return {
    loading,
    error,
    searchTerm,
    setSearchTerm,
    filteredKanban,
    filteredPublished,
    dynamicColumns: processedData.dynamicColumns
  };
}