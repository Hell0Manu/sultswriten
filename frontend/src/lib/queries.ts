import { gql } from '@apollo/client';

export const GET_DASHBOARD_DATA = gql`
  query GetDashboardData($authorIn: [ID]) {
    # 1. Configuração das Colunas
    workflowStatuses {
      slug
      label
      bgStyle   
      textStyle 
    }

    # 2. Dados do Usuário Logado
    viewer {
      databaseId
      roles {
        nodes {
          name 
        }
      }
    }

    # 3. Lista de Posts
    posts(
      first: 100
      where: { 
        authorIn: $authorIn
        stati: [PUBLISH, DRAFT, PENDING, SUSPENDED, REQUIRES_ADJUSTMENT, REVIEW_IN_PROGRESS, FINISHED, PENDING_IMAGE] 
      }
    ) {
      nodes {
        id
        databaseId
        title
        status
        date
        categories {
          nodes { name }
        }
        author {
          node {
            databaseId 
            name
            avatar { url }
          }
        }
      }
    }
  }
`;