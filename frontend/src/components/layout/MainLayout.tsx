import { useEffect } from "react";
import { Sidebar } from "./Sidebar";

interface MainLayoutProps {
  children: React.ReactNode;
}

export function MainLayout({ children }: MainLayoutProps) {
  
  useEffect(() => {
    document.body.classList.add("sults-fullscreen");
    return () => {
      document.body.classList.remove("sults-fullscreen");
    };
  }, []);

  return (
    <div className="flex h-screen w-full bg-zinc-50/50 overflow-hidden font-sans text-zinc-950">
      <Sidebar />
      <main className="flex-1 overflow-y-auto overflow-x-hidden p-8 bg-card">
        <div className="max-w-7xl mx-auto space-y-6 animate-in fade-in duration-500">
            {children}
        </div>
      </main>
    </div>
  );
}