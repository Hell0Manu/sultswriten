import { ApolloClient, InMemoryCache, createHttpLink } from '@apollo/client';
import { setContext } from '@apollo/client/link/context';


declare global {
  interface Window {
    sultsSettings: {
      rootUrl: string;
      nonce: string;
      adminUrl: string;
      user: { id: number; name: string; }
    };
  }

}
const LOCAL_WP_URL = ' http://sults-api.local/graphql'; 

const uri = import.meta.env.MODE === 'development' 
  ? LOCAL_WP_URL
  : '/graphql';  

const httpLink = createHttpLink({
  uri: uri,
});

const authLink = setContext((_, { headers }) => {
  const nonce = window.sultsSettings?.nonce || '';
  
  return {
    headers: {
      ...headers,
      'X-WP-Nonce': nonce, 
    }
  }
});

export const client = new ApolloClient({
  link: authLink.concat(httpLink),
  cache: new InMemoryCache(),
});