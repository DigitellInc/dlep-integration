# JWT (JSON Web Tokens)
Authentication to Digitell Live Events Platform is performed using JWT.

For more information on JWT specifications please see the following:

https://jwt.io/

https://tools.ietf.org/html/rfc7519

https://en.wikipedia.org/wiki/JSON_Web_Token

## Token Structure
The JSON schema for JWT authentication is available in [schema.json](schema.json). 
This document is to reflect examples that may be used to gain a top level understanding.

### JWT Claims
The claims (segment 2) of a JWT is the meat of the token. They represent the values which
are used to authorize access to a particular identity and session.

```json
{
  "session": "your_session_reference",
  "identity": { 
     "identifier": "ClientReference\\AttendeeUniqueID",
     "name": "AttendeeName",
     "email": "attendee@example.com"
   },
   "iat": 1553725786,
   "exp": 1553726086,
   "jti": "RandomNonceGoesHere",
   "aud": [
     "live.digitellinc.com",
     "live.digitellinc.com/api/browser/authorize/sl/identity"
   ]
}
```

### JWT Header
Third party libraries will automatically add the signing algorithm (which MUST be RS256) to the HEAD
of the JWT. However, in order that Digitell Live Events Platform can identify the key used to sign
the request, the "kid" (Key Identifier) MUST also be included in the head as so:

```json
{
  "typ": "JWT",
  "alg": "RS256",
  "kid": "ClientReference"
}
```
