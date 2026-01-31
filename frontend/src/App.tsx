import { HashRouter, Routes, Route } from 'react-router-dom';
import { ApolloProvider } from '@apollo/client/react';
import { client } from '@/lib/apollo';
import { ThemeProvider } from "@/components/theme-provider";
import { MainLayout } from '@/components/layout/MainLayout';
import { Dashboard } from "@/pages/Dashboard";

const StructurePage = () => <div className="p-6 text-white">Estrutura (Em breve)</div>;
const SettingsPage = () => <div className="p-6 text-white">Configurações (Em breve)</div>;
const ProfilePage = () => <div className="p-6 text-white">Perfil (Em breve)</div>;
const SupportPage = () => <div className="p-6 text-white">Suporte (Em breve)</div>;

function App() {
  return (
    <ThemeProvider defaultTheme="dark" storageKey="vite-ui-theme">
      <ApolloProvider client={client}>
        <HashRouter>
          <MainLayout>
            <Routes>
              <Route path="/" element={<Dashboard />} />
              
              <Route path="/structure" element={<StructurePage />} />
              <Route path="/settings" element={<SettingsPage />} />
              <Route path="/profile" element={<ProfilePage />} />
              <Route path="/support" element={<SupportPage />} />
            </Routes>
          </MainLayout>
        </HashRouter>
      </ApolloProvider>
    </ThemeProvider>
  );
}

export default App;