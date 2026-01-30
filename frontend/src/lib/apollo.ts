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

const httpLink = createHttpLink({
  uri: () => window.sultsSettings?.rootUrl ? window.sultsSettings.rootUrl.replace('/wp-json/', '/graphql') : '',
});

const authLink = setContext((_, { headers }) => {
  return {
    headers: {
      ...headers,
      'X-WP-Nonce': window.sultsSettings?.nonce,
    }
  }
});

export const client = new ApolloClient({
  link: authLink.concat(httpLink),
  cache: new InMemoryCache(),
});