import { Document } from "mongoose";

export interface IUser extends Document {
    _id: string;
    firstName: string;
    middleName?: string;
    lastName: string;
    userName: string;
    email: string;
    password: string;
    role: "user" | "admin" | "superadmin";
    status: "active" | "inactive" | "suspended";
    trash: boolean;
    createdAt: Date;
    updatedAt: Date;
}
