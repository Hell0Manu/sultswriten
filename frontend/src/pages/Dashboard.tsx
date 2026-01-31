import { useState, useMemo } from "react";
import { useQuery } from "@apollo/client/react"; 
import { GET_DASHBOARD_DATA } from "@/lib/queries"; 
import { Search, Plus, MessageSquare, Calendar, Loader2, CheckCircle2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Card, CardFooter, CardHeader } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { cn } from "@/lib/utils";

const canViewAllPosts = (roles: any[]) => {
  const adminRoles = ['administrator', 'editor_chefe']; 
  return roles?.some(r => adminRoles.includes(r.name)) || false;
};

export function Dashboard() {
  const [searchTerm, setSearchTerm] = useState("");
  
  const { data, loading, error } = useQuery(GET_DASHBOARD_DATA, {
    fetchPolicy: "network-only",
  });

  const { kanbanTasks, publishedTasks, dynamicColumns } = useMemo(() => {
    if (!data) return { kanbanTasks: [], publishedTasks: [], dynamicColumns: [] };

    const viewer = data.viewer;
    const isAdmin = viewer ? canViewAllPosts(viewer.roles.nodes) : false;
    const currentUserId = viewer?.databaseId;

    const allPosts = data.posts?.nodes?.map((post: any) => {
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

    const visiblePosts = isAdmin 
      ? allPosts 
      : allPosts.filter((p: any) => p.author.databaseId === currentUserId);

    const kanban = visiblePosts.filter((t: any) => t.status !== 'publish');
    const published = visiblePosts.filter((t: any) => t.status === 'publish');

    return {
      kanbanTasks: kanban,
      publishedTasks: published,
      dynamicColumns: data.workflowStatuses || []
    };
  }, [data]);

  const filteredKanban = kanbanTasks.filter((t: any) => t.title.toLowerCase().includes(searchTerm.toLowerCase()));
  const filteredPublished = publishedTasks.filter((t: any) => t.title.toLowerCase().includes(searchTerm.toLowerCase()));

  if (loading) return <div className="flex h-full items-center justify-center"><Loader2 className="animate-spin text-brand h-8 w-8" /></div>;
  if (error) return <div className="flex h-full items-center justify-center text-red-500">Erro ao carregar: {error.message}</div>;

  return (
    <div className="flex flex-col h-full w-full p-6 space-y-6 overflow-hidden">
      
      {/* Cabeçalho */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-white">Dashboard</h1>
        </div>
        
        <div className="relative w-full md:w-72">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
          <Input 
            placeholder="Pesquisar" 
            className="pl-10 rounded-full bg-zinc-900 border-zinc-700 text-white placeholder:text-zinc-500 focus-visible:ring-brand"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      <Tabs defaultValue="status" className="w-full flex-1 flex flex-col overflow-hidden">
        <div className="flex items-center justify-between border-b border-zinc-800 pb-4">
          <TabsList className="bg-transparent p-0 h-auto space-x-6">
            <TabsTrigger 
              value="status"
              className="bg-transparent p-0 text-base font-medium text-zinc-400 data-[state=active]:text-brand data-[state=active]:shadow-none relative after:content-[''] after:absolute after:-bottom-4 after:left-0 after:w-full after:h-[2px] after:bg-transparent data-[state=active]:after:bg-brand rounded-none transition-all"
            >
              Por status 
              <span className="ml-2 flex h-5 w-5 items-center justify-center rounded-full bg-zinc-800 text-xs text-zinc-300">
                {filteredKanban.length}
              </span>
            </TabsTrigger>
            <TabsTrigger 
              value="published"
              className="bg-transparent p-0 text-base font-medium text-zinc-400 data-[state=active]:text-brand data-[state=active]:shadow-none rounded-none transition-all"
            >
              Artigos publicados
              <span className="ml-2 flex h-5 w-5 items-center justify-center rounded-full bg-zinc-800 text-xs text-zinc-300">
                {filteredPublished.length}
              </span>
            </TabsTrigger>
          </TabsList>
        </div>

        {/* ABA KANBAN (Status) */}
        <TabsContent value="status" className="flex-1 mt-6 overflow-x-auto overflow-y-hidden pb-4">
          <div className="flex gap-6 h-full min-w-[1000px]">
            
            {dynamicColumns
              .filter((col: any) => col.slug !== 'publish') // Remove coluna de publicados do Kanban
              .map((col: any) => {
                const colTasks = filteredKanban.filter((t: any) => t.status === col.slug);
                
                return (
                  <div key={col.slug} className="flex-1 min-w-[280px] flex flex-col gap-4">
                    
                    {/* CABEÇALHO DA COLUNA */}
                    <div 
                      className="flex items-center justify-between p-2 rounded-full px-4 shadow-sm transition-all"
                      style={{ 
                        background: col.bgStyle, 
                        color: col.textStyle 
                      }}
                    >
                      <div className="flex items-center gap-2">
                        <span 
                          className="flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold"
                          style={{ backgroundColor: 'rgba(255,255,255,0.2)' }}
                        >
                          {colTasks.length}
                        </span>
                        <span className="font-medium text-sm whitespace-nowrap">
                          {col.label}
                        </span>
                      </div>
                      <Button size="icon" variant="ghost" className="h-6 w-6 rounded-full hover:bg-white/20" style={{ color: 'currentColor' }}>
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>

                    {/* LISTA DE CARDS */}
                    <div className="flex-1 space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                      {colTasks.map((task: any) => (
                        <Card key={task.id} className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition-shadow cursor-pointer group">
                            <CardHeader className="p-4 pb-2 space-y-0">
                            <div className="flex flex-wrap gap-2 mb-2">
                              {task.tags.map((tag: any, i: number) => (
                                <Badge key={i} className={cn("rounded-md px-2 py-0.5 text-[10px] font-bold shadow-none hover:opacity-80 border-0", tag.color)}>
                                  {tag.label}
                                </Badge>
                              ))}
                            </div>
                            <h3 className="font-bold text-sm leading-tight text-zinc-900 dark:text-zinc-100 group-hover:text-brand transition-colors">
                              {task.title}
                            </h3>
                          </CardHeader>
                          <CardFooter className="p-4 pt-2 flex items-center justify-between text-zinc-400">
                            <div className="flex -space-x-2">
                                <Avatar className="h-6 w-6 border-2 border-white dark:border-zinc-900">
                                  <AvatarImage src={task.author.avatar?.url} />
                                  <AvatarFallback className="text-[9px] bg-zinc-200 text-zinc-700">
                                    {task.author.name?.substring(0, 2).toUpperCase()}
                                  </AvatarFallback>
                                </Avatar>
                            </div>
                            <div className="flex items-center gap-3 text-xs font-medium">
                              <div className="flex items-center gap-1 hover:text-zinc-600 dark:hover:text-zinc-300">
                                <MessageSquare className="h-3.5 w-3.5" />
                                <span>{task.comments}</span>
                              </div>
                              <div className="flex items-center gap-1 hover:text-zinc-600 dark:hover:text-zinc-300">
                                <Calendar className="h-3.5 w-3.5" />
                                <span>{task.date}</span>
                              </div>
                            </div>
                          </CardFooter>
                        </Card>
                      ))}
                    </div>
                  </div>
                );
            })}
          </div>
        </TabsContent>
        
        {/* ABA PUBLICADOS */}
        <TabsContent value="published" className="flex-1 mt-6 overflow-y-auto custom-scrollbar">
           {filteredPublished.length === 0 ? (
             <div className="flex flex-col items-center justify-center h-64 text-zinc-500 border border-dashed border-zinc-800 rounded-xl bg-zinc-900/30">
                <CheckCircle2 className="h-10 w-10 mb-4 opacity-20" />
                <p>Nenhum artigo publicado encontrado.</p>
             </div>
           ) : (
             <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
                {filteredPublished.map((task: any) => (
                   <Card key={task.id} className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-brand/50 transition-colors cursor-pointer">
                      <CardHeader className="p-4">
                        <div className="flex justify-between items-start mb-2">
                           <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/20">Publicado</Badge>
                           <span className="text-xs text-zinc-500">{task.date}</span>
                        </div>
                        <h3 className="font-bold text-base text-zinc-900 dark:text-zinc-200 leading-snug">{task.title}</h3>
                      </CardHeader>
                      <CardFooter className="p-4 pt-0 text-sm text-zinc-500 flex justify-between items-center">
                         <div className="flex items-center gap-2">
                            <Avatar className="h-5 w-5">
                                <AvatarImage src={task.author.avatar?.url} />
                                <AvatarFallback className="text-[8px]">{task.author.name?.substring(0, 2)}</AvatarFallback>
                            </Avatar>
                            <span className="text-xs">{task.author.name}</span>
                         </div>
                      </CardFooter>
                   </Card>
                ))}
             </div>
           )}
        </TabsContent>
      </Tabs>
    </div>
  );
}