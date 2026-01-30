import { HashRouter, Routes, Route } from 'react-router-dom';
import { ApolloProvider } from '@apollo/client/react';
import { client } from '@/lib/apollo';
import { ThemeProvider } from "@/components/theme-provider";
import { MainLayout } from '@/components/layout/MainLayout'; 
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const WorkspacePage = () => (
    <div className="space-y-6">
        <div className="flex items-center justify-between">
            <div>
                <h2 className="text-3xl font-bold tracking-tight">Workspace</h2>
                <p className="text-muted-foreground">Gerencie seus artigos.</p>
            </div>
            <Button>+ Novo Artigo</Button>
        </div>
        <Card>
            <CardHeader><CardTitle>Posts</CardTitle></CardHeader>
            <CardContent>Tabela aqui</CardContent>
        </Card>
    </div>
);

const StructurePage = () => <div>Estrutura</div>;
const SettingsPage = () => <div>Configurações</div>;

function App() {
  return (
    <ThemeProvider defaultTheme="dark" storageKey="vite-ui-theme">
      <ApolloProvider client={client}>
        <HashRouter>
          <MainLayout>
            <Routes>
              <Route path="/" element={<WorkspacePage />} />
              <Route path="/structure" element={<StructurePage />} />
              <Route path="/settings" element={<SettingsPage />} />
            </Routes>
          </MainLayout>
        </HashRouter>
      </ApolloProvider>
    </ThemeProvider>
  );
}

export default App;