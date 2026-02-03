import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Card, CardHeader, CardFooter } from "@/components/ui/card";
import type { Task } from "@/features/dashboard/types";
import { Badge } from "@/components/ui/badge";

interface PublishedCardProps {
  task: Task;
}

export function PublishedCard({ task }: PublishedCardProps) {
  return (
    <Card className="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-brand/50 transition-colors cursor-pointer">
      <CardHeader className="p-4">
        <div className="flex justify-between items-start mb-2">
          <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/20">
            Publicado
          </Badge>
          <span className="text-xs text-zinc-500">{task.date}</span>
        </div>
        <h3 className="font-bold text-base text-zinc-900 dark:text-zinc-200 leading-snug">
          {task.title}
        </h3>
      </CardHeader>
      <CardFooter className="p-4 pt-0 text-sm text-zinc-500 flex justify-between items-center">
        <div className="flex items-center gap-2">
          <Avatar className="h-5 w-5">
            <AvatarImage src={task.author.avatar?.url} />
            <AvatarFallback className="text-[8px]">
              {task.author.name?.substring(0, 2)}
            </AvatarFallback>
          </Avatar>
          <span className="text-xs">{task.author.name}</span>
        </div>
      </CardFooter>
    </Card>
  );
}