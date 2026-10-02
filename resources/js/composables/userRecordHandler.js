import axios from "axios";

export default () => {
    return {
        fetch: (id) => axios.get(`/api/sample/${id}`), // view the record page (user record)
        save: (id) => axios.post(`/api/sample/${id}`),// after taking a assessment
    };
}

//also a object that holds the data so when detail is click, it doesnt need to request to the server, this file will hold that cache data hehe