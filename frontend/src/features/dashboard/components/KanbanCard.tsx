import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Card, CardHeader, CardFooter } from "@/components/ui/card";
import { MessageSquare, Calendar } from "lucide-react";
import type { Task } from "@/features/dashboard/types";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

interface KanbanCardProps {
  task: Task;
}

export function KanbanCard({ task }: KanbanCardProps) {
  return (
    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition-shadow cursor-pointer group">
      <CardHeader className="p-4 pb-2 space-y-0">
        <div className="flex flex-wrap gap-2 mb-2">
          {task.tags.map((tag, i) => (
            <Badge 
              key={i} 
              className={cn("rounded-md px-2 py-0.5 text-[10px] font-bold shadow-none hover:opacity-80 border-0", tag.color)}
            >
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
  );
}