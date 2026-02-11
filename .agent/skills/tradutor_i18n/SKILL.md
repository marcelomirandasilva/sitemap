# Skill: Internacionalização (i18n)

Esta skill automatiza a criação de sistemas bilingues (PT-BR / EN). Nunca escreva textos "hardcoded" nas Views.

## Regras de Frontend (Vue/Inertia)
1. **Sintaxe:** Use sempre a função de tradução: `{{ $t('chave.subchave') }}`.
   - ❌ Errado: `<button>Salvar</button>`
   - ✅ Certo: `<button>{{ $t('actions.save') }}</button>`

## Regra de Gestão de Arquivos
Sempre que criar uma nova chave de tradução no código (ex: `auth.login_title`):
1. **Leia** os arquivos `lang/pt.json` e `lang/en.json`.
2. **Verifique** se a chave já existe.
3. **Adicione** a nova chave em AMBOS os arquivos se faltar.
   - `lang/pt.json`: "Entrar no Sistema"
   - `lang/en.json`: "Login to System"

## Contexto
O projeto possui a pasta `lang/` na raiz. Mantenha os dois arquivos JSON sincronizados com as mesmas chaves.