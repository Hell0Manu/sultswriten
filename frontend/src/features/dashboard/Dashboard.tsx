import { Search, Plus, Loader2, CheckCircle2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";

// Imports Refatorados
import { useDashboardData } from "@/features/dashboard/hooks/useDashboardData";
import { KanbanCard } from "@/features/dashboard/components/KanbanCard";
import { PublishedCard } from "@/features/dashboard/components/PublishedCard";

export function Dashboard() {
  // 1. Hook que entrega tudo pronto
  const { 
    loading, 
    error, 
    searchTerm, 
    setSearchTerm, 
    filteredKanban, 
    filteredPublished, 
    dynamicColumns 
  } = useDashboardData();

  if (loading) return <div className="flex h-full items-center justify-center"><Loader2 className="animate-spin text-brand h-8 w-8" /></div>;
  if (error) return <div className="flex h-full items-center justify-center text-red-500">Erro ao carregar: {error.message}</div>;

  return (
    <div className="flex flex-col h-full w-full p-6 space-y-6 overflow-hidden">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h1 className="text-3xl font-bold tracking-tight text-white">Dashboard</h1>
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
        {/* Navegação das Abas */}
        <div className="flex items-center justify-between border-b border-zinc-800 pb-4">
          <TabsList className="bg-transparent p-0 h-auto space-x-6">
            <TabsTrigger value="status" className="tab-trigger-custom">
              Por status 
              <span className="badge-counter">{filteredKanban.length}</span>
            </TabsTrigger>
            <TabsTrigger value="published" className="tab-trigger-custom">
              Artigos publicados
              <span className="badge-counter">{filteredPublished.length}</span>
            </TabsTrigger>
          </TabsList>
        </div>

        {/* Conteúdo: Kanban */}
        <TabsContent value="status" className="flex-1 mt-6 overflow-x-auto overflow-y-hidden pb-4">
          <div className="flex gap-6 h-full min-w-[1000px]">
            {dynamicColumns
              .filter(col => col.slug !== 'publish')
              .map(col => {
                const colTasks = filteredKanban.filter(t => t.status === col.slug);
                return (
                  <div key={col.slug} className="flex-1 min-w-[280px] flex flex-col gap-4">
                    {/* Header da Coluna */}
                    <div 
                      className="flex items-center justify-between p-2 rounded-full px-4 shadow-sm transition-all"
                      style={{ background: col.bgStyle, color: col.textStyle }}
                    >
                      <div className="flex items-center gap-2">
                        <span className="flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold bg-white/20">
                          {colTasks.length}
                        </span>
                        <span className="font-medium text-sm whitespace-nowrap">{col.label}</span>
                      </div>
                      <Button size="icon" variant="ghost" className="h-6 w-6 rounded-full hover:bg-white/20 text-current">
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>

                    {/* Lista de Cards */}
                    <div className="flex-1 space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                      {colTasks.map(task => (
                        <KanbanCard key={task.id} task={task} />
                      ))}
                    </div>
                  </div>
                );
            })}
          </div>
        </TabsContent>
        
        {/* Conteúdo: Publicados */}
        <TabsContent value="published" className="flex-1 mt-6 overflow-y-auto custom-scrollbar">
           {filteredPublished.length === 0 ? (
             <div className="flex flex-col items-center justify-center h-64 text-zinc-500 border border-dashed border-zinc-800 rounded-xl bg-zinc-900/30">
                <CheckCircle2 className="h-10 w-10 mb-4 opacity-20" />
                <p>Nenhum artigo publicado encontrado.</p>
             </div>
           ) : (
             <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
                {filteredPublished.map(task => (
                   <PublishedCard key={task.id} task={task} />
                ))}
             </div>
           )}
        </TabsContent>
      </Tabs>
    </div>
  );
}