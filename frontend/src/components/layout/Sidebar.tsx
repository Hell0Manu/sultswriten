import { useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { 
  Network, Settings, LogOut, Headset, Moon, Sun, Home, ChevronRight, Menu
} from "lucide-react"; 
import { cn } from "@/lib/utils"; 
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { useTheme } from "@/components/theme-provider";
import { Sheet, SheetContent, SheetTrigger, SheetTitle, SheetDescription } from "@/components/ui/sheet";

const menuItems = [
  { label: "Dashboard", icon: Home, path: "/" },
  { label: "Estrutura", icon: Network, path: "/structure" },
  { label: "Configurações", icon: Settings, path: "/settings" },
];

export function Sidebar() {
  const location = useLocation();
  const { theme, setTheme } = useTheme(); 
  const [isCollapsed, setIsCollapsed] = useState(true);
  const [isMobileOpen, setIsMobileOpen] = useState(false);
  const toggleSidebar = () => setIsCollapsed(!isCollapsed);
  const toggleTheme = () => {setTheme(theme === "dark" ? "light" : "dark");};
  
  {/* Ícone de Hamburger */}
  const ToggleButton = () => (
    <button
      onClick={toggleSidebar}
      className="flex h-7 w-7 items-center justify-center text-neutral-400 hover:!text-white transition-colors cursor-pointer"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
    </button>
  );

  const user = window.sultsSettings?.user || { name: "Usuário", email: "redator@sults.com" };

  const SidebarContent = ({ isMobile = false }: { isMobile?: boolean }) => {
    const collapsed = isMobile ? false : isCollapsed;
    const showText = !collapsed;

    return (
      <div className="flex flex-col h-full w-full sults-sidebar">
        {/* Cabeçalho */}
        <div className={cn("flex h-14 items-center justify-between px-4 mb-2 shrink-0", isMobile && "mt-4")}>
          <div className="flex items-center gap-3 overflow-hidden">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-800 border border-zinc-700/50">
              <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M29.0238 15.9808L11.6951 33.3094C11.5324 33.4721 11.6409 33.7433 11.8849 33.7433H20.1289C20.1832 33.7433 20.2645 33.7162 20.3188 33.662L31.4644 22.5163C32.7119 21.2689 32.7119 19.2621 31.4644 18.0418L29.4034 15.9808C29.295 15.8723 29.1322 15.8723 29.0238 15.9808Z" fill="#00ACAC"/>
                <path d="M26.7995 13.7302L13.6741 26.8284C13.5657 26.9369 13.403 26.9369 13.3216 26.8284L9.19961 22.7064C9.09114 22.5979 9.09114 22.4352 9.19961 22.3539L22.2978 9.22857C22.4063 9.1201 22.569 9.1201 22.6503 9.22857L26.7723 13.3506C26.8808 13.459 26.8808 13.6218 26.7995 13.7302Z" fill="#00ACAC"/>
                <path d="M15.6812 2.39432L4.53558 13.54C3.28813 14.7874 3.28813 16.7942 4.53558 18.0145L6.59658 20.0755C6.70505 20.184 6.86776 20.184 6.94912 20.0755L24.2778 2.77398C24.4405 2.61127 24.332 2.34009 24.088 2.34009H15.844C15.7897 2.34009 15.7084 2.36721 15.6812 2.39432Z" fill="#00ACAC"/>
              </svg>
            </div>

            {showText && (
              <h1 className="text-xl font-bold tracking-tight text-white whitespace-nowrap">
                Artigos
              </h1>
            )}
          </div>

          {!collapsed && !isMobile && <ToggleButton />}
        </div>

        {/* Menu Principal */}
        <nav className="flex-1 space-y-1 px-3 overflow-y-auto overflow-x-hidden">
          {menuItems.map((item) => {
            const isActive = location.pathname === item.path;
            return (
              <Link
                key={item.path}
                to={item.path}
                title={collapsed ? item.label : ""} 
                onClick={() => isMobile && setIsMobileOpen(false)}
                className={cn(
                  "flex items-center gap-4 rounded-lg p-3 text-sm font-semibold transition-all duration-200 group relative",
                  isActive 
                    ? "bg-zinc-700/50 !text-white" 
                    : "!text-neutral-400 hover:bg-zinc-700/50 hover:!text-white",
                  collapsed && "justify-center px-2"
                )}
              >
                <item.icon size={20} className={cn("shrink-0", isActive ? "text-brand" : "text-neutral-400 group-hover:!text-white")} />
                
                <span className={cn(
                  "whitespace-nowrap transition-all duration-300 overflow-hidden",
                  showText ? "w-auto opacity-100 block" : "w-0 opacity-0 hidden"
                )}>
                  {item.label}
                </span>
              </Link>
            );
          })}
        </nav>

        {/* Rodapé */}
        <div className="p-3 space-y-1 shrink-0">
          
          {/* Botão Tema */}
          <button
            onClick={toggleTheme}
            title={collapsed ? "Alternar Tema" : ""}
            className={cn(
              "w-full flex items-center gap-4 rounded-lg p-3 transition-all duration-200 group relative",
              "!text-sm !font-semibold", 
              "!text-neutral-400 hover:!bg-zinc-800/50 hover:!text-white cursor-pointer",
              collapsed && "justify-center px-2"
            )}
          >
            {theme === 'dark' ? <Sun size={20} className="shrink-0" /> : <Moon size={20} className="shrink-0" />}
            <span className={cn(
              "whitespace-nowrap transition-all duration-300 overflow-hidden",
              showText ? "w-auto opacity-100 block" : "w-0 opacity-0 hidden"
            )}>
              Tema {theme === 'dark' ? 'escuro' : 'claro'}
            </span>
          </button>

          {/* Botão Suporte */}
          {(() => {
            const isSupportActive = location.pathname === "/support";
            return (
              <Link
                to="/support"
                title={collapsed ? "Suporte" : ""}
                onClick={() => isMobile && setIsMobileOpen(false)}
                className={cn(
                  "w-full flex items-center gap-4 rounded-lg p-3 transition-all duration-200 group relative",
                  "!text-sm !font-semibold", 
                  
                  isSupportActive 
                    ? "!bg-zinc-700/50 !text-white shadow-sm" 
                    : "!text-neutral-400 hover:!bg-zinc-800/50 hover:!text-white",
                    
                  collapsed && "justify-center px-2"
                )}
              >
                <Headset 
                  size={20} 
                  className={cn(
                    "shrink-0 transition-colors", 
                    isSupportActive ? "!text-brand" : "group-hover:!text-white"
                  )} 
                />
                <span className={cn(
                  "whitespace-nowrap transition-all duration-300 overflow-hidden",
                  showText ? "w-auto opacity-100 block" : "w-0 opacity-0 hidden"
                )}>
                  Suporte
                </span>
              </Link>
            );
          })()}

          {/* Botão Sair */}
          <button
            onClick={() => window.location.href = window.sultsSettings?.adminUrl}
            title={collapsed ? "Sair" : ""}
            className={cn(
              "w-full flex items-center gap-4 rounded-lg p-3 transition-all duration-200 group relative cursor-pointer",
              "!text-sm !font-semibold",
              "!text-neutral-400 hover:!bg-zinc-800/50 hover:!text-white",
              collapsed && "justify-center px-2"
            )}
          >
            <LogOut size={20} className="shrink-0 transition-colors group-hover:!text-white" />
            <span className={cn(
              "whitespace-nowrap transition-all duration-300 overflow-hidden",
              showText ? "w-auto opacity-100 block" : "w-0 opacity-0 hidden"
            )}>
              Sair
            </span>
          </button>

          {/* Card de Usuário */}
          <Link 
            to="/profile"
            onClick={() => isMobile && setIsMobileOpen(false)}
            className={cn(
              "flex w-full items-center gap-3 rounded-xl border border-transparent p-2 transition-colors hover:bg-zinc-800 group",
              collapsed && "justify-center border-0 p-0"
            )}
          >
            <Avatar className="h-9 w-9 border border-zinc-700 cursor-pointer shrink-0">
              <AvatarImage src="" /> 
              <AvatarFallback className="bg-brand text-white text-xs font-bold">
                {user.name?.substring(0, 2).toUpperCase() || "EU"}
              </AvatarFallback>
            </Avatar>
            
            <div className={cn("flex-1 overflow-hidden transition-all duration-300 text-left", showText ? "w-auto block" : "w-0 hidden")}>
              <p className="truncate text-sm font-semibold text-zinc-200 group-hover:!text-white">
                {user.name}
              </p>
              <p className="truncate text-xs text-zinc-500">
                {user.email || user.user_email || "redator@sults.com"}
              </p>
            </div>

            {showText && <ChevronRight size={16} className="text-zinc-600 group-hover:text-zinc-400 shrink-0" />}
          </Link>
        </div>
      </div>
    );
  };

  return (
    <>
      <div className={cn("md:hidden fixed top-3 left-3 z-[100]", isMobileOpen && "hidden")}>
        <Sheet open={isMobileOpen} onOpenChange={setIsMobileOpen}>
          <SheetTrigger asChild>
            <Button variant="outline" size="icon" className="h-10 w-10 bg-zinc-900 border-zinc-700 text-white hover:bg-zinc-800 shadow-xl">
              <Menu size={20} />
            </Button>
          </SheetTrigger>
          <SheetContent side="left" className="p-0 bg-zinc-900 border-zinc-800 text-white w-72">
            <SheetTitle className="sr-only">Menu</SheetTitle>
            <SheetDescription className="sr-only">Navegação principal</SheetDescription>
            <SidebarContent isMobile={true} />
          </SheetContent>
        </Sheet>
      </div>

      {/* Desktop sidebar */}
      <aside 
        className={cn(
          "flex relative flex-col h-screen bg-zinc-900 border-r border-zinc-800 z-50 transition-all duration-300 ease-in-out pt-6",
          "max-md:hidden",
          isCollapsed ? "w-20" : "w-72" 
        )}
      >
        {isCollapsed && (
          <div className="absolute -right-3 top-7 z-50 pt-8">
            <Button
              variant="secondary"
              size="icon"
              className="h-6 w-6 rounded-full border border-zinc-700 bg-zinc-800 text-neutral-400 hover:!text-white shadow-md"
              onClick={toggleSidebar}
            >
              <ToggleButton />
            </Button>
          </div>
        )}

        <SidebarContent />
      </aside>
    </>
  );
}