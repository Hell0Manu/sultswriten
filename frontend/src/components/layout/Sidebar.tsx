import { useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { 
  LayoutDashboard, Network, Settings, LogOut, FileText, 
  ChevronLeft, ChevronRight, MoreVertical, User, LifeBuoy,
  Moon, Sun, Laptop 
} from "lucide-react"; 
import { cn } from "@/lib/utils"; 
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";
import { useTheme } from "@/components/theme-provider"; 
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuSub,
  DropdownMenuSubTrigger,
  DropdownMenuSubContent,
  DropdownMenuPortal
} from "@/components/ui/dropdown-menu";

const menuItems = [
  { label: "Workspace", icon: LayoutDashboard, path: "/" },
  { label: "Estrutura", icon: Network, path: "/structure" },
  { label: "Configurações", icon: Settings, path: "/settings" },
];

export function Sidebar() {
  const location = useLocation();
  const [isCollapsed, setIsCollapsed] = useState(false);
  const { setTheme } = useTheme(); 

  const toggleSidebar = () => setIsCollapsed(!isCollapsed);

  return (
    <aside 
      className={cn(
        "relative flex flex-col h-screen border-r border-zinc-800 bg-zinc-950 text-zinc-50 shadow-xl z-50 transition-all duration-300 ease-in-out",
        isCollapsed ? "w-20" : "w-72" 
      )}
    >
      <div className="absolute -right-3 top-7 z-50">
        <Button
          variant="secondary"
          size="icon"
          className="h-6 w-6 rounded-full border border-zinc-700 bg-zinc-800 text-zinc-400 hover:text-white shadow-md"
          onClick={toggleSidebar}
        >
          {isCollapsed ? <ChevronRight size={14} /> : <ChevronLeft size={14} />}
        </Button>
      </div>

      <div className={cn("flex items-center gap-3 p-6", isCollapsed && "justify-center px-2")}>
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 shadow-blue-900/20 shadow-lg">
          <FileText className="text-white h-6 w-6" />
        </div>
        
        <div className={cn("overflow-hidden transition-all duration-300", isCollapsed ? "w-0 opacity-0" : "w-auto opacity-100")}>
          <h1 className="text-lg font-bold tracking-tight text-white whitespace-nowrap">
            Sults Writen
          </h1>
          <p className="text-xs text-zinc-500 whitespace-nowrap">Editorial System 2.0</p>
        </div>
      </div>

      <Separator className="bg-zinc-800/50 mx-4 w-auto" />

      <nav className="flex-1 space-y-2 p-4 overflow-y-auto overflow-x-hidden">
        {menuItems.map((item) => {
          const isActive = location.pathname === item.path;
          return (
            <Link
              key={item.path}
              to={item.path}
              title={isCollapsed ? item.label : ""} 
              className={cn(
                "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 group relative",
                isActive 
                  ? "bg-blue-600 text-white shadow-md" 
                  : "text-zinc-400 hover:bg-zinc-800/50 hover:text-white",
                isCollapsed && "justify-center px-2"
              )}
            >
              <item.icon size={20} className={cn("shrink-0", isActive ? "text-white" : "text-zinc-400 group-hover:text-white")} />
              
              <span className={cn(
                "whitespace-nowrap transition-all duration-300 overflow-hidden",
                isCollapsed ? "w-0 opacity-0 hidden" : "w-auto opacity-100 block"
              )}>
                {item.label}
              </span>

              {isActive && !isCollapsed && (
                <div className="absolute right-2 h-1.5 w-1.5 rounded-full bg-white/50" />
              )}
            </Link>
          );
        })}
      </nav>

      <Separator className="bg-zinc-800/50 mx-4 w-auto" />

      <div className="p-4">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <button className={cn(
              "flex w-full items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-2 text-left transition-colors hover:bg-zinc-800 hover:text-white outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:ring-offset-zinc-950",
              isCollapsed && "justify-center border-0 bg-transparent p-0 hover:bg-transparent"
            )}>
              <Avatar className="h-9 w-9 border border-zinc-700 cursor-pointer">
                <AvatarImage src="" /> 
                <AvatarFallback className="bg-blue-900 text-blue-100 text-xs font-bold">
                  {window.sultsSettings?.user?.name?.substring(0, 2).toUpperCase() || "EU"}
                </AvatarFallback>
              </Avatar>
              
              <div className={cn("flex-1 overflow-hidden transition-all duration-300", isCollapsed ? "w-0 hidden" : "w-auto block")}>
                <p className="truncate text-sm font-medium text-zinc-200">
                  {window.sultsSettings?.user?.name || "Usuário"}
                </p>
                <p className="truncate text-xs text-zinc-500">
                  {window.sultsSettings?.user?.name ? "redator@sults.com" : "Redator"}
                </p>
              </div>

              {!isCollapsed && <MoreVertical size={16} className="text-zinc-500" />}
            </button>
          </DropdownMenuTrigger>

          <DropdownMenuContent className="w-56 bg-zinc-950 border-zinc-800 text-zinc-300" align="end" side="right" sideOffset={10}>
            <DropdownMenuLabel className="text-zinc-500 text-xs font-normal uppercase tracking-wider">
              Minha Conta
            </DropdownMenuLabel>
            
            <DropdownMenuItem className="focus:bg-zinc-800 focus:text-white cursor-pointer">
              <User className="mr-2 h-4 w-4" />
              <span>Perfil</span>
            </DropdownMenuItem>

            {/* SUBMENU DE TEMA */}
            <DropdownMenuSub>
              <DropdownMenuSubTrigger className="focus:bg-zinc-800 focus:text-white cursor-pointer">
                <Sun className="mr-2 h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                <Moon className="absolute mr-2 h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
                <span>Tema</span>
              </DropdownMenuSubTrigger>
              <DropdownMenuPortal>
                <DropdownMenuSubContent className="bg-zinc-950 border-zinc-800 text-zinc-300">
                  <DropdownMenuItem onClick={() => setTheme("light")} className="focus:bg-zinc-800 cursor-pointer">
                    <Sun className="mr-2 h-4 w-4" /> Claro
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setTheme("dark")} className="focus:bg-zinc-800 cursor-pointer">
                    <Moon className="mr-2 h-4 w-4" /> Escuro
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setTheme("system")} className="focus:bg-zinc-800 cursor-pointer">
                    <Laptop className="mr-2 h-4 w-4" /> Sistema
                  </DropdownMenuItem>
                </DropdownMenuSubContent>
              </DropdownMenuPortal>
            </DropdownMenuSub>
            
            <DropdownMenuItem className="focus:bg-zinc-800 focus:text-white cursor-pointer">
              <LifeBuoy className="mr-2 h-4 w-4" />
              <span>Suporte</span>
            </DropdownMenuItem>
            
            <DropdownMenuSeparator className="bg-zinc-800" />
            
            <DropdownMenuItem 
              className="text-red-400 focus:bg-red-950/30 focus:text-red-300 cursor-pointer"
              onClick={() => window.location.href = window.sultsSettings?.adminUrl}
            >
              <LogOut className="mr-2 h-4 w-4" />
              <span>Sair para o Painel</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </aside>
  );
}